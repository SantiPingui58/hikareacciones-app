<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VODs de Hikarilof</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #AA61E2;
        }
        .container {
            margin-top: 50px;
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
        .btn-discord {
            background-color: #7289da;
            color: white;
        }
        .btn-discord:hover {
            background-color: #5b6eae;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card text-center">
                    <div class="card-body">
                        <h1 class="card-title">Hika Reacciones </h1>
                        <p class="card-text">Para ver los VODs de Twitch lo antes posible y sin censura, necesitas ser suscriptor en <a href="https://twitch.tv/hikarilof" target="_blank">twitch.tv/hikarilof</a>.</p>
                        <div class="mt-4">
                            <a href="https://discord.gg/ZCAxTqjcCj" target="_blank" class="btn btn-discord btn-lg">Únete al Discord</a>
                            <a href="/auth/twitch" class="btn btn-primary btn-lg">Iniciar Sesión con Twitch</a>
                            <a href="https://twitch.tv/hikarilof" target="_blank" class="btn btn-custom btn-lg">Ir al Canal de Twitch</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carrusel de suscriptores -->
        <div class="row mt-5">
            <div class="col-12">
                <div id="subscriberCarousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        <!-- Aquí se mostrarán las tarjetas de los suscriptores -->
                        @foreach ($subscribers as $index => $subscriber)
                            <div class="carousel-item {{ $index == 0 ? 'active' : '' }}">
                                <div class="card">
                                    <img src="{{ $subscriber['profile_image_url'] }}" class="card-img-top" alt="Imagen de perfil">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $subscriber['user_name'] }}</h5>
                                        <p class="card-text">¡Gracias por ser un suscriptor!</p>
                                        <a href="https://twitch.tv/{{ $subscriber['user_name'] }}" class="btn btn-custom">Ver Canal</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Controles del carrusel -->
                    <a class="carousel-control-prev" href="#subscriberCarousel" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Anterior</span>
                    </a>
                    <a class="carousel-control-next" href="#subscriberCarousel" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Siguiente</span>
                    </a>
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
