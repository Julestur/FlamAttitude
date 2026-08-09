<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="stylesheet" href="{{ asset('css/mobile-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleconnexion.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page-connexion-fixe.css') }}?v={{ filemtime(public_path('css/page-connexion-fixe.css')) }}">
    <link rel="icon" id="iconOnglet" href="{{ asset('Images/LogoFlamattitudeNoir.png') }}" type="image/png">

    <title>Connexion</title>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="page-connexion-fixe">



<!-- Affichage -->

<div id="attenteConnexionAppareil">
    <h3 id="attenteConnexionTitre">Flam'Attitude</h3>
    <div id="attenteConnexionSpinner"></div>
    <p>Connexion en cours…</p>
</div>

<div id="connexion-centre">

<div id="logoFlamattitude">
    <img id="logoFlamattitude" src="{{ asset('Images/logo2-noBG.png') }}" alt="logo de Flam'Attitude">
</div>

<form action="{{ url('/login-process') }}" method="POST" class="portail">
    @csrf
    <input type="email" name="email" value="{{ old('email') }}" placeholder="Adresse e-mail" class="contenu-portail" required autofocus>
    <hr>
    <div class="champ-mdp">
        <input type="password" name="mot-de-passe" id="mdp" placeholder="Mot de passe" class="contenu-portail" required>
        <button type="button" class="toggle-mdp" data-cible="mdp" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
    </div>
    <hr>

    @if(isset($session_expiree) && $session_expiree)
    <p id="erreur" style="background-color: #e67e22;">
        <ion-icon name="time-outline"></ion-icon> 
        Votre session a expiré, veuillez vous reconnecter.
        <ion-icon name="time-outline"></ion-icon>
    </p>

    @elseif($errors->has('login_error'))
        <p id="erreur">
            <ion-icon name="close-outline"></ion-icon>
            {{ $errors->first('login_error') }}
            <ion-icon name="close-outline"></ion-icon>
        </p>

    @elseif($errors->has('email') || $errors->has('mot-de-passe'))
        <p id="erreur">
            <ion-icon name="close-outline"></ion-icon>
            {{ $errors->first('email') ?: $errors->first('mot-de-passe') }}
            <ion-icon name="close-outline"></ion-icon>
        </p>

    @elseif($errors->has('message') && str_contains($errors->first(), 'expired'))
        <p id="erreur" style="background-color: #e67e22;">
            <ion-icon name="time-outline"></ion-icon>
            Session expirée, merci de réessayer.
            <ion-icon name="time-outline"></ion-icon>
        </p>
    @endif

    <div class="bouton">
        <input type="submit" value="Connexion" id="bouton-style-connexion">
    </div>

    <div class="info">
        <a href="{{ url('/mot-de-passe-oublie') }}">Mot de passe oublié ?</a>
    </div>
    <div class="info">
        <a href="{{ url('/inscription') }}"> Pas encore de compte ?</a>
    </div>
</form>

</div>



</body>


<!-- Gestion du logo Flam'Attitude dans l'onglet  -->
<script> 
window.laravelAssets = {lightIcon: "{{ asset('Images/LogoFlamattitudeNoir.png') }}",darkIcon: "{{ asset('Images/LogoFlamattitudeBlanc.png') }}"};
</script>
<script src="{{ asset('js/iconOnglet.js') }}"></script>
<script src="{{ asset('js/togglePassword.js') }}"></script>
<script src="{{ asset('js/connexion-appareil.js') }}"></script>



</html>