
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



<!-- Affichage -->

<div id="logoFlamattitude">
    <img id="logoFlamattitude" src="{{ asset('Images/logo2.png') }}" alt="logo de Flam'Attitude">
</div>
<h3 id="titre">Flam'Attitude</h3>
<p class="txtVerif">Étape 1 sur 3 : votre identité</p>



<form action="{{ route('inscription.Etape1_GESTION') }}" method="POST" class="portail">
    @csrf

    @if($errors->any())
        <p style="color: red;">{{ $errors->first() }}</p>
    @endif

    <input type="text" name="nom" style="font-size:20px;" placeholder="Nom" class="contenu-portail" value="{{ old('nom', $donnees['nom'] ?? '') }}" required>
    <hr>
    <input type="text" name="prenom" style="font-size:20px;" placeholder="Prénom" class="contenu-portail" value="{{ old('prenom', $donnees['prenom'] ?? '') }}" required>
    <hr>
    <input type="tel" name="telephone" style="font-size:20px;" placeholder="Téléphone" class="contenu-portail" value="{{ old('telephone', $donnees['telephone'] ?? '') }}" required>
    <hr>
    <input type="text" name="adresse" style="font-size:20px;" placeholder="Adresse (rue, ville, code postal)" class="contenu-portail" value="{{ old('adresse', $donnees['adresse'] ?? '') }}" required>
    <hr>

    <div class="bouton">
        <input type="submit" value="Continuer" id="bouton-style-connexion">
    </div>
</form>

<br><br>


<p class="info" style="margin-top:30px;margin-bottom:100px;">
    <a href="{{ route('login') }}" style="color: white;">Retour</a>
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
