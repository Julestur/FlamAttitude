@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/accueilAdmin.css') }}">
<link rel="stylesheet" href="{{ asset('css/accueilStyle.css') }}">

<div class="page-dimension">
    <div class="accueil">
        <h2 class="titre1">{{ $salutation }}, {{ $prenom }} !</h2>
    </div>

    <h2 class="titre2">Tableau de bord</h2>
    <div class="tab_bord">
        <div class="carre_style">
            <div class="contenu_carte"><h3>Clients</h3><p class="valeur">{{ $stats['clients'] }}</p></div>
            <ion-icon name="people-outline" class="style_icone"></ion-icon>
        </div>
        <div class="carre_style">
            <div class="contenu_carte"><h3>Mes disponibilités à venir</h3><p class="valeur">{{ $stats['mesDisponibilites'] }}</p></div>
            <ion-icon name="calendar-outline" class="style_icone"></ion-icon>
        </div>
        <div class="carre_style">
            <div class="contenu_carte"><h3>Mes RDV à venir</h3><p class="valeur">{{ $stats['mesRdvAVenir'] }}</p></div>
            <ion-icon name="checkmark-circle-outline" class="style_icone"></ion-icon>
        </div>
    </div>

    <h2 class="titre2">Actions rapides</h2>
    <div class="barre_choix">
        <a href="{{ route('clients.recherche') }}" class="bouton_choix">
            <ion-icon name="search-outline"></ion-icon> Rechercher un client
        </a>
        <a href="{{ route('creneaux.gestion') }}" class="bouton_choix">
            <ion-icon name="calendar-outline"></ion-icon> Mes disponibilités
        </a>
    </div>
</div>
@endsection
