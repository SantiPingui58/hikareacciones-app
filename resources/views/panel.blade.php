<?php
use App\Models\TwitchUser;

$user = TwitchUser::where('twitch_id', session('user_id'))->first();
if (!$user) {
    return redirect()->route('home');
}

// Verificar si el usuario se creó hace menos de 5 segundos
$isRecentlyCreated = $user->created_at->diffInSeconds(now()) < 5;

?>

@if(session('success'))
    <div class="alert alert-success" role="alert">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger" role="alert">
        {{ session('error') }}
    </div>
@endif

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Suscriptor</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #AA61E2;
        }
        .container {
            margin-top: 200px;
        }
        .card {
            background-color: #DEADED;
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }
        .btn-custom {
            background-color: #9146FF;
            color: white;
        }
        .btn-custom:hover {
            background-color: #772ce8;
        }
        .btn-logout {
            background-color: #dc3545;
            color: white;
        }
        .btn-logout:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card text-center">
                    <div class="card-body">
                        <h1 class="card-title">Bienvenido {{ $user->display_name }}</h1>
                        <img src="{{ $user->profile_image_url }}" class="rounded-circle" alt="Perfil de Twitch" width="150">
                        <form action="/request-access" method="POST">
                            @csrf
                            <?php if (!$isRecentlyCreated): ?>
                                <a href="https://drive.google.com/drive/folders/14ujv5c_kCP0sv_incV6s-Oic0w_ABeA3?usp=drive_link" target="_blank" class="btn btn-success btn-lg mb-3">Ir al Google Drive</a>
                                <div class="form-group">
                                    <label for="email">Si deseas modificar tu email de acceso al Drive, ingresa un nuevo correo electrónico:</label>
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                    <label for="email">Bienvenido, para solicitar acceso al Drive donde se suben todas las VODs deberás ingresar tu email y dar click al botón de solicitar acceso.</label>
                                </div>
                            <?php endif; ?>
                            <div class="form-group">
                                <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                            </div>
                            <?php if ($isRecentlyCreated): ?>
                                <button type="submit" class="btn btn-primary btn-lg">Solicitar Acceso a Drive</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary btn-lg">Modificar Email de Acceso a Drive</button>
                            <?php endif; ?>
                        </form>
                        <a href="/logout" class="btn btn-danger mt-3">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
