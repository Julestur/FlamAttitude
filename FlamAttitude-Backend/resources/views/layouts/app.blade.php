@php
    $estAppMobile = \App\Support\Plateforme::estAppMobile(request());
@endphp
<!DOCTYPE html>
<html lang="fr" class="{{ $estAppMobile ? 'app-native' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="stylesheet" href="{{ asset('css/mobile-app.css') }}">
    <title>@yield('title', "Flam'Attitude")</title>

    <link rel="stylesheet" href="{{ asset('css/header.css') }}">

    <link rel="stylesheet" href="{{ asset('css/accueilAdmin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleDropZone.css') }}">
    
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">


    <link rel="icon" id="iconOnglet" href="{{ asset('Images/LogoFlamattitudeNoir.png') }}" type="image/png">

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

    @include('partials.header')

    <main>
        @yield('content')
    </main>


    @include('partials.footer')

    @if($estAppMobile)
        @include('partials.bottom-nav')
    @endif

    </body>
    
    
    <script src="{{ asset('js/app.js') }}"></script>



    <!-- Gestion du logo Flam'Attitude dans l'onglet  -->
    <script> 
    window.laravelAssets = {lightIcon: "{{ asset('Images/LogoFlamattitudeNoir.png') }}",darkIcon: "{{ asset('Images/LogoFlamattitudeBlanc.png') }}"};
    </script>
    <script src="{{ asset('js/iconOnglet.js') }}"></script>
    <script src="{{ asset('js/togglePassword.js') }}"></script>

    <script>
    window.FLAMATTITUDE_STAFF = @json(in_array((int) session('idStatut'), [\App\Support\Roles::ADMIN, \App\Support\Roles::MEMBRE_ENTREPRISE]));
    window.FLAMATTITUDE_NOUVEAU_JETON_APPAREIL = @json(session('nouveau_jeton_appareil'));
    </script>
    <script src="{{ asset('js/jeton-appareil.js') }}"></script>
    <script src="{{ asset('js/verrou-app.js') }}"></script>
</html>