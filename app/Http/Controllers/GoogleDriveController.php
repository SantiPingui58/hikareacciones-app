<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_Permission;
use App\Models\TwitchUser;
use Illuminate\Support\Facades\Log;

class GoogleDriveController extends Controller
{

    public function accessFromForm(Request $request) {
        $email = $request->input('email');
       return $this->access($email);
    }

    public function access($newUserEmail)
    {
        $user = TwitchUser::where('twitch_id', session('user_id'))->first();
        if (!$user || !$user->sub_activa) {
			Log::warning('El usuario'.$user->display_name.' figura que no existe o tiene sub no activa en BBDD');
            return redirect()->route('home');
        }


if (TwitchUser::where('email', $newUserEmail)->where('twitch_id', '!=', $user->twitch_id)->exists()) {
	Log::warning('El usuario '.$user->id. ' ha intentado registrar el mail de otra persona: '.$newUserEmail);
    return redirect()->back()->with('error', 'El email '.$newUserEmail.' está asociado a otra cuenta de Twitch.');
}

$currentUserEmail = $user->email;
$user->email = $newUserEmail;
$user->save();
Log::warning('Actualizando el email del usuario '.$user->display_name);
            // ID del archivo al que deseas dar acceso
            $fileId = "1CZtJ_yJFXrzoe5-uCWSnYaF5liQYl1gn";

            $client = new Google_Client();
            $client->setAuthConfig(base_path('reacciones-app.json')); 
            $client->addScope(Google_Service_Drive::DRIVE);
            $driveService = new Google_Service_Drive($client);
       
            try {
                $permissions = $driveService->permissions->listPermissions($fileId, array(
                    'fields' => 'permissions(id, emailAddress, role)'
                ))->getPermissions();



                foreach ($permissions as $permission) {
                    if ($permission->getEmailAddress() === $currentUserEmail && $permission->getRole() === 'reader') {
                        $driveService->permissions->delete($fileId, $permission->getId());
                    }
                }

              
                // Crea un nuevo objeto de permiso
                $newPermission = new Google_Service_Drive_Permission([
                    'type' => 'user',
                    'role' => 'reader', 
                    'emailAddress' => $newUserEmail
                ]);

                // Agrega el nuevo permiso al archivo
                $driveService->permissions->create($fileId, $newPermission);
			Log::info('Drive compartido con: '.$newUserEmail);

            } catch (\Exception $e) {
				Log::error('Error al compartir Drive:'. $e->getMessage());
                return redirect('/panel')->with('error', 'Error al compartir el archivo: ' . $e->getMessage());
            }
        
	
         return redirect('/panel')->with('success', 'Se ha compartido el archivo correctamente con ' . $newUserEmail); 
    }

    public function removeAccess($email)
    {
        $fileId = "1CZtJ_yJFXrzoe5-uCWSnYaF5liQYl1gn";
        $client = new Google_Client();
        $client->setAuthConfig(base_path('reacciones-app.json'));
        $client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($client);
    
        try {
            $permissions = $driveService->permissions->listPermissions($fileId, array(
                'fields' => 'permissions(id, emailAddress, role)'
            ))->getPermissions();
    
            $accessRemoved = false;
            
            foreach ($permissions as $permission) {
                if ($permission->getEmailAddress() === $email) {
                    $driveService->permissions->delete($fileId, $permission->getId());
                    Log::info("Se ha eliminado el acceso del Drive para: " . $email);
                    $accessRemoved = true;
                    break;
                }
            }
    
            if (!$accessRemoved) {
                Log::info("El mail " . $email . " no tenia acceso al Drive");
            }
    
        } catch (\Exception $e) {
            Log::error('Error al remover acceso de Drive: ' . $e->getMessage());
        }
    }
    
}
