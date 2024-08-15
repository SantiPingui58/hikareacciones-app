<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\TwitchUser;
use App\Http\Controllers\GoogleDriveController;
use Carbon\Carbon;

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

        $accessToken = $response->json()['access_token'];

        // Obtener la información del usuario
        $userInfo = $this->getUserInfo($accessToken);

        // Buscar o crear el usuario en la base de datos
        $twitchUser = TwitchUser::firstOrCreate(
            ['twitch_id' => $userInfo['id']],
            [
                'display_name' => $userInfo['display_name'],
                'profile_image_url' => $userInfo['profile_image_url'],
                'email' => $userInfo['email'],
                'sub_activa' => false, // Usar valor booleano
            ]
        );
        

           // Verificar si el usuario es nuevo
           $isNewUser = $twitchUser->wasRecentlyCreated;

           // Guardar en la sesión si el usuario es nuevo
           session(['new_user' => $isNewUser]);

        // Verificar si el usuario tiene una suscripción activa
        if ($this->hasSuscription($userInfo['id'], $accessToken)) {
            if ($twitchUser->sub_activa == 0) {
                // Si no tenía una suscripción activa, se la activamos
                $twitchUser->sub_activa = 1;
                $twitchUser->end_sub_date = Carbon::now()->addDays(33);
                $twitchUser->save();
                $googleDriveController = new GoogleDriveController();
                //$googleDriveController->requestAccess($user->email);
                $googleDriveController->requestAccess('test@gmail.santi.com');
            }

            session(['user_id' => $userInfo['id']]);
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
}
