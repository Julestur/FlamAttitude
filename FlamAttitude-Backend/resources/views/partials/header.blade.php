@php
    $estAppMobile = \App\Support\Plateforme::estAppMobile(request());
@endphp

<link rel="stylesheet" href="{{ asset('css/header.css') }}">
<link rel="stylesheet" href="{{ asset('css/accueilStyle.css') }}">
@if($estAppMobile)
    <link rel="stylesheet" href="{{ asset('css/app-native.css') }}">
@endif



<div id="header-wrapper">
    <header>

        <div id="profil">
            @if(Session::get('photo-profil'))
                <img src="{{ asset('images_profil/' . Session::get('photo-profil')) }}" alt="Photo de profil">
            @else
                <ion-icon name="person-circle-outline" style="font-size: 125px; color: white;"></ion-icon>
            @endif

            <div id="profil-info">
                <p>{{ Session::get('prenom') }}</p>
                <small>{{ Session::get('grade') }}</small>
            </div>

        </div>

         <div class="titre-wrapper">
            <div class="titre-divider"></div>
            <h1 class="titre">Flam'Attitude</h1>
        </div>

        <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    </header>

    {{--
        En dehors de <header> volontairement : <header> a un backdrop-filter, qui fait de lui
        le "containing block" de tout descendant en position:fixed (comportement CSS standard).
        Ça coinçait le menu déroulant dans les 300px de hauteur du header sur mobile au lieu du
        vrai écran, cachant les premières options en haut. Le JS cible ces éléments par id, donc
        leur position dans le DOM n'a pas d'importance pour le fonctionnement du menu.
    --}}
    <div id="overlay"></div>

    <div id="profil-deroulant">
        <p>profil</p>
        <ul>
            @unless($estAppMobile)
            <li>
                <a href="{{ route('accueil') }}">
                    <span>accueil</span>
                    <ion-icon name="school-outline"></ion-icon>
                </a>
            </li>
            @endunless
            <li>
                <a href="{{ route('aff.changementMDP') }}">
                    <span>changer mot de passe</span>
                    <ion-icon name="shield-half-outline"></ion-icon>
                </a>
            </li>
            <li>
                <a href="{{ route('aff.changementPDP') }}">
                    <span>changer photo de profil</span>
                    <ion-icon name="person-circle-outline"></ion-icon>
                </a>
            </li>

            @if(Session::get('idStatut') == \App\Support\Roles::CLIENT && ! $estAppMobile)
                <li>
                    <a href="{{ route('mon-espace.accueil') }}">
                        <span>mes documents et factures</span>
                        <ion-icon name="folder-outline"></ion-icon>
                    </a>
                </li>
                <li>
                    <a href="{{ route('mon-espace.creneaux') }}">
                        <span>mes rendez-vous</span>
                        <ion-icon name="calendar-outline"></ion-icon>
                    </a>
                </li>
            @endif

            @if(in_array(Session::get('idStatut'), [\App\Support\Roles::ADMIN, \App\Support\Roles::MEMBRE_ENTREPRISE]) && ! $estAppMobile)
                <li>
                    <a href="{{ route('clients.recherche') }}">
                        <span>rechercher un client</span>
                        <ion-icon name="search-outline"></ion-icon>
                    </a>
                </li>
                <li>
                    <a href="{{ route('creneaux.monEdt') }}">
                        <span>mon EDT</span>
                        <ion-icon name="calendar-outline"></ion-icon>
                    </a>
                </li>
                <li>
                    <a href="{{ route('creneaux.gestion') }}">
                        <span>mes disponibilités</span>
                        <ion-icon name="checkbox-outline"></ion-icon>
                    </a>
                </li>
            @endif

            @if(Session::get('idStatut') == \App\Support\Roles::ADMIN)
                <li>
                    <a href="{{ route('inscriptionAdmin.Etape1_VU') }}">
                        <span>ajouter un utilisateur</span>
                        <ion-icon name="add-circle-outline"></ion-icon>
                    </a>
                </li>
                <li>
                    <a href="{{ route('suppressionAdmin.Etape1_VU') }}">
                        <span>supprimer un utilisateur</span>
                        <ion-icon name="remove-circle-outline"></ion-icon>
                    </a>
                </li>
                <li>
                    <a href="{{ route('typesRdv.gestion') }}">
                        <span>types de RDV</span>
                        <ion-icon name="pricetags-outline"></ion-icon>
                    </a>
                </li>
            @endif

            <li>
                <a class="red" href="{{ route('logout') }}">
                    <span>deconnexion</span>
                    <ion-icon name="exit-outline"></ion-icon>
                </a>
            </li>
        </ul>
    </div>

    {{-- Doit rester après #overlay et #profil-deroulant : ce script les récupère par id dès son exécution. --}}
    <script src="{{ asset('js/menuDeroulant.js') }}"></script>
</div>
