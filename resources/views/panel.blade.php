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
        .modal .modal-body {
            text-align: center;
        }
        .modal .modal-body strong {
            font-weight: bold;
        }
        h2 {
            color: black;
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
                            <a href="https://drive.google.com/drive/folders/1CZtJ_yJFXrzoe5-uCWSnYaF5liQYl1gn?usp=drive_link" target="_blank" class="btn btn-success btn-lg mb-3">Ir al Google Drive</a>
                            <div class="form-group">
                                <label for="email">Si deseas modificar tu email de acceso al Drive, ingresa un nuevo correo electrónico:</label>
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">
                                {{ 'Modificar Email de Acceso a Drive'}}
                            </button>
                        </form>
                        <a href="/logout" class="btn btn-danger mt-3">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Título de Suscriptores -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="text-center">Suscriptores del canal</h2>
            </div>
        </div>

        <!-- Fila de suscriptores -->
        <div class="row mt-3">
            <!-- Aquí se mostrarán las tarjetas de los suscriptores -->
            @foreach ($subscribers as $index => $subscriber)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 mb-4">
                    <div class="card">
                        <!-- Imagen de perfil o imagen por defecto -->
                        <img src="{{ $subscriber['profile_image_url'] ?? asset('images/user.jpg') }}" class="card-img-top" alt="Imagen de perfil">
                        <div class="card-body">
                            <h5 class="card-title">{{ $subscriber['user_name'] }}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal -->
    @if(session('new_user'))
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Usuario Registrado</h5>
                </div>
                <div class="modal-body">
                    ¡Usuario registrado correctamente! El correo electrónico de acceso al Drive es <strong>{{ $user->email }}</strong>. Puedes modificarlo en la siguiente ventana si lo deseas o si tienes problemas para acceder al Drive.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    @if(session('new_user'))
        $(document).ready(function() {
            $('#successModal').modal('show');
        });
    @endif
    </script>
</body>
</html>
