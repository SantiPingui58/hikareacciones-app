<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TwitchUser;
use App\Http\Controllers\GoogleDriveController; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;  // Importar Log

class CheckTwitchUserSubscriptions extends Command
{
    protected $signature = 'twitch:check-subs';
    protected $description = 'Verifica los usuarios de Twitch cuya suscripción haya vencido o esté por vencer.';

    public function handle()
    {
        $yesterday = Carbon::yesterday()->toDateString();
        
        $users = TwitchUser::whereDate('end_sub_date', '<=', $yesterday)
            ->where('sub_activa', 1) 
            ->get();

        if ($users->isEmpty()) {
            Log::info('No hay usuarios con suscripciones expiradas o por vencer.');
        } else {
            // Crear una instancia del controlador de GoogleDrive
            $googleDriveController = new GoogleDriveController();

            foreach ($users as $user) {
                $googleDriveController->removeAccess($user->email);

                $user->sub_activa = 0;
                $user->save();  

                Log::info("Acceso removido y suscripción desactivada para el usuario: {$user->email}");
            }
        }
    }
}
