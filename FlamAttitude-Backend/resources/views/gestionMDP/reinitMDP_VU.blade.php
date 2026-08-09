
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="stylesheet" href="{{ asset('css/mobile-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleconnexion.css') }}">
    <link rel="icon" id="iconOnglet" href="{{ asset('Images/LogoFlamattitudeNoir.png') }}" type="image/png">

    <title>Réinitialisation du mot de passe</title>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>



<!-- Affichage -->

<div id="logoFlamattitude">
    <img id="logoFlamattitude" src="{{ asset('Images/logo2.png') }}" alt="logo de Flam'Attitude">
</div>
<h3 id="titre">Réinitialisation du mot de passe</h3>





<div class="page-dimension">
        <div class="contenu-principal">    
            



            <form action="{{ route('reinitMDP_GESTION') }}" method="POST" class="portail">
                @csrf


                <!-- Affichage des erreurs au dessus de du remplissage des champs -->
                @if ($errors->any())
                    <div style="color: #fa021b; background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;">
                        <ul style="margin: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('erreur'))
                    <p style="color: red; text-align: center; font-weight: bold;">{{ session('erreur') }}</p>
                @endif

                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="champ-mdp">
                    <input type="password" placeholder="Nouveau Mot De Passe" class="contenu-portail" name="password" id="password" required>
                    <button type="button" class="toggle-mdp" data-cible="password" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
                </div>
                <hr>
                <div class="champ-mdp">
                    <input type="password" placeholder="Confirmation" class="contenu-portail" name="password_confirmation" id="password_confirmation" required>
                    <button type="button" class="toggle-mdp" data-cible="password_confirmation" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
                </div>
                <hr>

                <div class="bouton">
                    <input type="submit" value="Enregistrer" id="bouton-style-connexion">
                </div>
            </form>
        </div>
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
<script src="{{ asset('js/togglePassword.js') }}"></script>



</html>
</body>
</html>