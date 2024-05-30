<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_Permission;
use App\Models\TwitchUser;

class GoogleDriveController extends Controller
{
    public function requestAccess(Request $request)
    {
        $user = TwitchUser::where('twitch_id', session('user_id'))->first();
        if (!$user) {
            return redirect()->route('home');
        }

        $currentUserEmail = $user->email;
        $newUserEmail = $request->input('email');

        // Verificar si el email introducido es diferente al email actual del usuario
        if ($newUserEmail !== $currentUserEmail) {

// Verificar si ya existe algún otro usuario con el mismo email
if (TwitchUser::where('email', $newUserEmail)->where('twitch_id', '!=', $user->twitch_id)->exists()) {
    return redirect()->back()->with('error', 'El email '.$newUserEmail.' está asociado a otra cuenta de Twitch.');
}

// Actualizar el email del usuario
$user->email = $newUserEmail;
$user->save();

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

               
            } catch (\Exception $e) {
                return redirect('/panel')->with('error', 'Error al compartir el archivo: ' . $e->getMessage());
            }
        }
         return redirect('/panel')->with('success', 'Se ha compartido el archivo correctamente con ' . $newUserEmail); 
    }
}
