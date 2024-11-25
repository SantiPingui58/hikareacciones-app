<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\TwitchUser;
use App\Http\Controllers\GoogleDriveController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

   public function index() {
    // Suponiendo que getSubscribers() devuelve un JSON, lo decodificamos a un array
    $subscribers = $this->getSubscribers();
    $subscribers = json_decode($subscribers, true);
    return view('home', ['subscribers' => $subscribers]);
}



    public function twitchLogin()
    {
        $query = http_build_query([
            'client_id' => env('TWITCH_CLIENT_ID'),
            'redirect_uri' => env('TWITCH_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'user:read:subscriptions channel:read:subscriptions user:read:email',
        ]);

        return redirect('https://id.twitch.tv/oauth2/authorize?' . $query);
    }

    public function twitchCallback(Request $request)
    {
        // Obtener el token de acceso
        $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
            'client_id' => env('TWITCH_CLIENT_ID'),
            'client_secret' => env('TWITCH_CLIENT_SECRET'),
            'code' => $request->code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => env('TWITCH_REDIRECT_URI'),
        ]);
    
        if ($response->failed()) {
            Log::error('Error al obtener el token de acceso: ' . $response->body());
            return redirect()->route('home')->with('error', 'Hubo un problema al conectarte con Twitch.');
        }

        $accessToken = $response->json()['access_token'];
        $refreshToken = $response->json()['refresh_token'];
        $expiresIn = $response->json()['expires_in'];
    
        // Obtener la información del usuario
        $userInfo = $this->getUserInfo($accessToken);
        Log::info('User Info: ' . json_encode($userInfo));
        Log::info('Token expires at: ' . Carbon::now()->addSeconds($expiresIn));
        // Si el broadcaster_id es 697850700, almacenar el token en el modelo
        if ($userInfo['id'] === '697850700') {
            $twitchUser = TwitchUser::updateOrCreate(
                ['twitch_id' => $userInfo['id']],
                [
                    'display_name' => $userInfo['display_name'],
                    'profile_image_url' => $userInfo['profile_image_url'],
                    'email' => $userInfo['email'] ?? null,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expiration' => Carbon::now()->addSeconds($expiresIn),
                ]
            );
            Log::info('Token almacenado para broadcaster: ' . $userInfo['display_name']);
        }
    
        // Continuar con el flujo normal para cualquier usuario
        $twitchUser = TwitchUser::firstOrCreate(
            ['twitch_id' => $userInfo['id']],
            [
                'display_name' => $userInfo['display_name'],
                'profile_image_url' => $userInfo['profile_image_url'],
                'email' => $userInfo['email'] ?? null,
                'sub_activa' => false,
            ]
        );
        Log::info('Login o registro del usuario: ' . $twitchUser->id);
    
        // Verificar si el usuario es nuevo
        $isNewUser = $twitchUser->wasRecentlyCreated;
        Log::info('Nuevo usuario: ' . ($isNewUser ? 'Sí' : 'No'));
    
        // Guardar en la sesión si el usuario es nuevo
        session(['new_user' => $isNewUser]);
        session(['user_id' => $userInfo['id']]);
    
        // Verificar si el usuario tiene una suscripción activa
        if ($this->hasSuscription($userInfo['id'], $accessToken)) {
            Log::info($twitchUser->display_name . ' tiene suscripción activa.');
            if (!$twitchUser->sub_activa) {
                // Activar la suscripción
                $twitchUser->sub_activa = true;
                $twitchUser->end_sub_date = Carbon::now()->addDays(33);
                $twitchUser->save();
                Log::info('Suscripción activada para ' . $twitchUser->display_name . '. Finaliza el: ' . $twitchUser->end_sub_date);
    
                $googleDriveController = new GoogleDriveController();
                if (!is_null($twitchUser->email)) {
                    $googleDriveController->access($twitchUser->email);
                    Log::info('Acceso al Drive concedido a ' . $twitchUser->display_name . ' (' . $twitchUser->email . ').');
                } else {
                    Log::warning('No se puede conceder acceso al Drive. Email nulo para ' . $twitchUser->display_name);
                }
            }
    
            return redirect()->route('panel')->with('twitchUser', $twitchUser);
        } else {
            return redirect()->route('home')->with('error', 'No estás suscrito al canal de hikarilof!');
        }
    }
    

    private function getUserInfo($accessToken)
    {
        $userResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Client-Id' => env('TWITCH_CLIENT_ID'),
        ])->get('https://api.twitch.tv/helix/users');

        return $userResponse->json()['data'][0];
    }

    private function hasSuscription($userId, $accessToken)
    {
        $subscriptionResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Client-Id' => env('TWITCH_CLIENT_ID'),
        ])->get('https://api.twitch.tv/helix/subscriptions/user?broadcaster_id=697850700&user_id=' . $userId);

        return $subscriptionResponse->status() === 200;
    }

    public function showPanel()
    {
        $user = TwitchUser::where('twitch_id', session('user_id'))->first();
        if (!$user) {
            return redirect()->route('home');
        }

        return view('panel');
    }

    public function logout(Request $request)
    {
        // Eliminar el ID del usuario de la sesión
        $request->session()->forget('user_id');
        // Redirigir a la página de inicio
        return redirect()->route('home');
    }

  public function getSubscribers()
{
    // Recuperar el usuario de Hika desde la base de datos
    $twitchUser = TwitchUser::where('twitch_id', 697850700)->first();

    if (!$twitchUser || !$twitchUser->access_token) {
        return response()->json(['error' => 'La página actualmente no funciona.'], 401);
    }

    $accessToken = $twitchUser->access_token;
    $broadcasterId = $twitchUser->twitch_id; 
    $allSubscribers = [];
    $cursor = null;

    do {
        // Realizar la solicitud a la API de Twitch para obtener los suscriptores
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Client-Id' => env('TWITCH_CLIENT_ID'),
        ])->get("https://api.twitch.tv/helix/subscriptions", [
            'broadcaster_id' => $broadcasterId,
            'after' => $cursor,  
            'first' => 100  
        ]);


        if ($response->successful()) {
            $subscribers = $response->json()['data'];
			$missingProfileUsers = [];
            foreach ($subscribers as &$subscriber) {
				 $twitchUser = TwitchUser::where('twitch_id', $subscriber['user_id'])->first();
				if ($twitchUser && $twitchUser->profile_image_url) {
					$subscriber['profile_image_url'] = $twitchUser->profile_image_url;
				} else {
					$missingProfileUsers[] = $subscriber['user_id'];
				}
            }
			
			
			 if (!empty($missingProfileUsers)) {
                $userResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Client-Id' => env('TWITCH_CLIENT_ID'),
                ])->get('https://api.twitch.tv/helix/users', [
                    'id' => $missingProfileUsers
                ]);

                if ($userResponse->successful()) {
                    $userData = $userResponse->json()['data'] ?? [];

                    foreach ($userData as $user) {
                        TwitchUser::updateOrCreate(
                            ['twitch_id' => $user['id']],
                            [
                                'profile_image_url' => $user['profile_image_url'],
                                'display_name' => $user['display_name']
                            ]
                        );

                        foreach ($subscribers as &$subscriber) {
                            if ($subscriber['user_id'] === $user['id']) {
                                $subscriber['profile_image_url'] = $user['profile_image_url'];
                                break;
                            }
                        }
                    }
                }
            }


            $allSubscribers = array_merge($allSubscribers, $subscribers);

            $cursor = $response->json()['pagination']['cursor'] ?? null;

        } else {
            $errorMessage = $response->json()['message'] ?? 'No se pudieron obtener los suscriptores';
            $errorCode = $response->json()['status'] ?? 400;
            return response()->json(['error' => $errorMessage], $errorCode);
        }
    } while ($cursor);  

    return response()->json($allSubscribers)->getContent();
}

    

}
