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
    public function twitchLogin()
    {
        $query = http_build_query([
            'client_id' => env('TWITCH_CLIENT_ID'),
            'redirect_uri' => env('TWITCH_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'user:read:subscriptions user:read:email',
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
        Log::info('Response: ' . json_encode($response->json()));
        $accessToken = $response->json()['access_token'];
        $refreshToken = $response->json()['refresh_token'];
        $expiresIn = $response->json()['expires_in'];
    
        // Obtener la información del usuario
        $userInfo = $this->getUserInfo($accessToken);
        Log::info('User Info: ' . json_encode($userInfo));
    
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

    public function getSubscribers(Request $request)
    {

        // Recuperar el usuario de Hika desde la base de datos
        $twitchUser = TwitchUser::where('twitch_id', 697850700)->first();
    
        // Verificar si el usuario está autenticado y tiene un access_token
        if (!$twitchUser || !$twitchUser->access_token) {
            return response()->json(['error' => 'La página actualmente no funciona.'], 401);
        }
    
        // Utilizar el access_token del usuario autenticado
        $accessToken = $twitchUser->access_token;
    
        // Obtener el broadcaster_id desde el usuario de Twitch o configurarlo manualmente
        $broadcasterId = '697850700';  // Puedes cambiar esto por el ID del broadcaster que quieras, o obtenerlo del usuario autenticado si es necesario
    
        // Realizar la solicitud a la API de Twitch para obtener los suscriptores
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Client-Id' => env('TWITCH_CLIENT_ID'),
        ])->get("https://api.twitch.tv/helix/subscriptions", [
            'broadcaster_id' => $broadcasterId
        ]);
    
        // Verificar si la respuesta fue exitosa
        if ($response->successful()) {
            // Obtener los datos de los suscriptores
            $subscribers = $response->json()['data'];
            return response()->json($subscribers);
        } else {
            // Si no fue exitosa, capturar el mensaje de error
            $errorMessage = $response->json()['message'] ?? 'No se pudieron obtener los suscriptores';
            $errorCode = $response->json()['status'] ?? 400; 
            return response()->json(['error' => $errorMessage], $errorCode);
        }
    }
    

}
