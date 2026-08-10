<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Placeholder' }}</title>
        <link rel="stylesheet" href="{{ asset('css/components/site-navbar.css') }}?v={{ filemtime(public_path('css/components/site-navbar.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/components/site-footer.css') }}?v={{ filemtime(public_path('css/components/site-footer.css')) }}">
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
            }
            main {
                min-height: 60vh;
                margin: 40px;
            }
        </style>
    </head>
    <body>
        @include('layouts.partials.site-navbar')
        <main id="contenido-principal">
            <h1>{{ $title ?? 'Placeholder' }}</h1>
            <p>Esta pantalla se migrara en una fase posterior.</p>
        </main>
        @include('layouts.partials.site-footer')
        <script src="{{ asset('js/site-navbar.js') }}?v={{ filemtime(public_path('js/site-navbar.js')) }}" defer></script>
    </body>
</html>
