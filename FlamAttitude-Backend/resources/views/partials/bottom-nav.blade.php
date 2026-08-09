@php
    $idStatutNav = (int) session('idStatut');
    $routeActuelle = (string) (request()->route()?->getName() ?? '');
@endphp

<nav id="app-bottom-nav">
    @if($idStatutNav === \App\Support\Roles::CLIENT)
        <a href="{{ route('accueil') }}" class="{{ $routeActuelle === 'accueil' ? 'actif' : '' }}">
            <ion-icon name="home-outline"></ion-icon>
            <span>Accueil</span>
        </a>
        <a href="{{ route('mon-espace.creneaux') }}" class="{{ str_starts_with($routeActuelle, 'mon-espace.creneaux') ? 'actif' : '' }}">
            <ion-icon name="calendar-outline"></ion-icon>
            <span>RDV</span>
        </a>
        <a href="{{ route('mon-espace.accueil') }}" class="{{ $routeActuelle === 'mon-espace.accueil' ? 'actif' : '' }}">
            <ion-icon name="folder-outline"></ion-icon>
            <span>Documents</span>
        </a>
    @else
        <a href="{{ route('accueil') }}" class="{{ $routeActuelle === 'accueil' ? 'actif' : '' }}">
            <ion-icon name="home-outline"></ion-icon>
            <span>Accueil</span>
        </a>
        <a href="{{ route('clients.recherche') }}" class="{{ str_starts_with($routeActuelle, 'clients.') ? 'actif' : '' }}">
            <ion-icon name="search-outline"></ion-icon>
            <span>Clients</span>
        </a>
        <a href="{{ route('creneaux.monEdt') }}" class="{{ $routeActuelle === 'creneaux.monEdt' ? 'actif' : '' }}">
            <ion-icon name="calendar-outline"></ion-icon>
            <span>Mon EDT</span>
        </a>
        <a href="{{ route('creneaux.gestion') }}" class="{{ in_array($routeActuelle, ['creneaux.gestion', 'creneaux.grille', 'creneaux.toggle']) ? 'actif' : '' }}">
            <ion-icon name="checkbox-outline"></ion-icon>
            <span>Dispos</span>
        </a>
    @endif
</nav>
