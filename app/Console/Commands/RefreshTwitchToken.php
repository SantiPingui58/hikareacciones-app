<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\TwitchUser;
use Carbon\Carbon;

class RefreshTwitchToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch:refresh-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresca el token de acceso de Twitch cada hora';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Buscar el primer usuario con refresh_token
        $twitchUser = TwitchUser::whereNotNull('refresh_token')->first();

        if ($twitchUser) {
            // Realizar la solicitud para obtener el nuevo token de acceso
            $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
                'client_id' => env('TWITCH_CLIENT_ID'),
                'client_secret' => env('TWITCH_CLIENT_SECRET'),
                'refresh_token' => $twitchUser->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $accessToken = $response->json()['access_token'];
                $refreshToken = $response->json()['refresh_token'];
                $expiresIn = $response->json()['expires_in']; // El tiempo de expiración en segundos

                // Calcular la fecha de expiración
                $tokenExpiration = Carbon::now()->addSeconds($expiresIn);

                // Actualizar el token y la fecha de expiración en la base de datos
                $twitchUser->access_token = $accessToken;
                $twitchUser->refresh_token = $refreshToken;
                $twitchUser->token_expiration = $tokenExpiration;
                $twitchUser->save();

                $this->info('Token y fecha de expiración actualizados correctamente.');
            } else {
                $this->error('Error al actualizar el token de Twitch.');
            }
        } else {
            $this->error('No se encontró un usuario con refresh token.');
        }
    }
}
