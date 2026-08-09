
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="stylesheet" href="{{ asset('css/mobile-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleconnexion.css') }}">
    <link rel="icon" id="iconOnglet" href="{{ asset('Images/LogoFlamattitudeNoir.png') }}" type="image/png">

    <title>Inscription</title>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

<div id="logoFlamattitude">
    <img id="logoFlamattitude" src="{{ asset('Images/logo2.png') }}" alt="logo de Flam'Attitude">
</div>
<h3 id="titre">Flam'Attitude</h3>
<p class="txtVerif">Étape 3 sur 3 : votre mot de passe</p>

<form action="{{ route('inscription.Etape3_GESTION') }}" method="POST" class="portail">
    @csrf

    @if($errors->any())
        <p style="color: red;">{{ $errors->first() }}</p>
    @endif

    <div class="champ-mdp">
        <input type="password" name="mot-de-passe" id="mdp" style="font-size:20px;" placeholder="Mot de passe (10 caractères min., majuscule, minuscule, chiffre, symbole)" class="contenu-portail" required>
        <button type="button" class="toggle-mdp" data-cible="mdp" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
    </div>
    <hr>
    <div class="champ-mdp">
        <input type="password" name="mot-de-passe_confirmation" id="mdp_confirmation" style="font-size:20px;" placeholder="Confirmation" class="contenu-portail" required>
        <button type="button" class="toggle-mdp" data-cible="mdp_confirmation" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
    </div>
    <hr>

    <div class="bouton">
        <input type="submit" value="Créer mon compte" id="bouton-style-connexion">
    </div>
</form>

<br><br>

<p class="info" style="margin-top:30px;margin-bottom:100px;">
    <a href="{{ route('inscription.Etape2_VU') }}" style="color: white;">Retour</a>
</p>

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
<script src="{{ asset('js/togglePassword.js') }}"></script>

</html>
