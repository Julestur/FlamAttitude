<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="stylesheet" href="{{ asset('css/mobile-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleconnexion.css') }}">
    <link rel="icon" id="iconOnglet" href="{{ asset('Images/LogoFlamattitudeNoir.png') }}" type="image/png">
    <title>Réinitialisation Mot de Passe</title>
</head>
<body>


<h1 id="logoFlamattitude"><img src="{{ asset('Images/logo2.png') }}" alt="logo"></h1>
<h3 id="titre">RÉINITIALISATION DU MOT DE PASSE</h3>

<form action="{{ route('password.email.process') }}" method="POST" class="portail">
    @csrf <fieldset id="contour-form">
        <input type="text" 
               placeholder="adresse e-mail de récupération" class="contenu-portail" style="font-size:20px;" name="email" value="{{ old('email') }}" required>
        <hr>

        @if(session('erreur'))
            <p style="margin-top: 10px; text-align: center; color: red; font-size: 20px; font-family: helvetica;">
                {{ session('erreur') }}
            </p>
        @endif

        <div class="bouton">
            <input type="submit" value="Réinitialiser" id="bouton-style-connexion">
        </div> 
    </fieldset>
</form>

@if(session('confirmation'))
    <p style="margin-top: 10px; text-align: center; color: green; font-size: 17px; font-family: helvetica;">
        {{ session('confirmation') }}
    </p>
@endif

<div class="info" style="padding-top: 4%; font-size: x-large;">
    <a href="{{ route('login') }}">Retour <<<</a>
</div>


<!-- Footer  -->
<footer class="footer">
    <p>&copy; {{ date('Y') }}, Flam'Attitude</p>
    <p id="footerLogo"><img id="logo" src="{{ asset('Images/LogoFlamattitudeBlanc.png') }}"></p>
</footer>

</body>


<!-- Gestion du logo Flam'Attitude dans l'onglet  -->
<script> 
window.laravelAssets = {lightIcon: "{{ asset('Images/LogoFlamattitudeNoir.png') }}",darkIcon: "{{ asset('Images/LogoFlamattitudeBlanc.png') }}"};
</script>
<script src="{{ asset('js/iconOnglet.js') }}"></script>





</body>
</html>