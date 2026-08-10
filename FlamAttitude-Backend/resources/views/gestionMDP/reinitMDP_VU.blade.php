<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="stylesheet" href="{{ asset('css/mobile-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleconnexion.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page-connexion-fixe.css') }}?v={{ filemtime(public_path('css/page-connexion-fixe.css')) }}">
    <link rel="icon" id="iconOnglet" href="{{ asset('Images/LogoFlamattitudeNoir.png') }}" type="image/png">

    <title>Réinitialisation du mot de passe</title>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="page-connexion-fixe">

<div id="connexion-centre">

    <div id="logoFlamattitude">
        <img id="logoFlamattitude" src="{{ asset('Images/logo2-noBG.png') }}" alt="logo de Flam'Attitude">
    </div>

    <form action="{{ route('reinitMDP_GESTION') }}" method="POST" class="portail">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="champ-mdp">
            <input type="password" placeholder="Nouveau mot de passe" class="contenu-portail" name="password" id="password" required>
            <button type="button" class="toggle-mdp" data-cible="password" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
        </div>
        <hr>
        <div class="champ-mdp">
            <input type="password" placeholder="Confirmation" class="contenu-portail" name="password_confirmation" id="password_confirmation" required>
            <button type="button" class="toggle-mdp" data-cible="password_confirmation" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
        </div>
        <hr>

        @if ($errors->any())
            <p id="erreur">
                <ion-icon name="close-outline"></ion-icon>
                {{ $errors->first() }}
                <ion-icon name="close-outline"></ion-icon>
            </p>
        @elseif (session('erreur'))
            <p id="erreur">
                <ion-icon name="close-outline"></ion-icon>
                {{ session('erreur') }}
                <ion-icon name="close-outline"></ion-icon>
            </p>
        @endif

        <div class="bouton">
            <input type="submit" value="Enregistrer" id="bouton-style-connexion">
        </div>
    </form>

</div>

</body>

<script src="{{ asset('js/togglePassword.js') }}"></script>

</html>
