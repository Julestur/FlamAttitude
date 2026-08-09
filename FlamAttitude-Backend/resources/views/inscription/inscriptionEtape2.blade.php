
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="stylesheet" href="{{ asset('css/mobile-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleconnexion.css') }}">
    <link rel="icon" id="iconOnglet" href="{{ asset('Images/LogoFlamattitudeNoir.png') }}" type="image/png">

    <title>Inscription</title>
</head>
<body>

<div id="logoFlamattitude">
    <img id="logoFlamattitude" src="{{ asset('Images/logo2.png') }}" alt="logo de Flam'Attitude">
</div>
<h3 id="titre">Flam'Attitude</h3>
<p class="txtVerif">Étape 2 sur 3 : votre identifiant</p>

<form action="{{ route('inscription.Etape2_GESTION') }}" method="POST" class="portail">
    @csrf

    @if($errors->any())
        <p style="color: red;">{{ $errors->first() }}</p>
    @endif

    <input type="text" name="pseudo" style="font-size:20px;" placeholder="Identifiant" class="contenu-portail" value="{{ old('pseudo', $donnees['pseudo'] ?? '') }}" required>
    <hr>
    <input type="email" name="email" style="font-size:20px;" placeholder="Adresse e-mail" class="contenu-portail" value="{{ old('email', $donnees['email'] ?? '') }}" required>
    <hr>

    <div class="bouton">
        <input type="submit" value="Continuer" id="bouton-style-connexion">
    </div>
</form>

<br><br>

<p class="info" style="margin-top:30px;margin-bottom:100px;">
    <a href="{{ route('inscription.Etape1_VU') }}" style="color: white;">Retour</a>
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

</html>
