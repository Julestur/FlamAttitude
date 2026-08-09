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
            <div class="contenu_carte"><h3>Membres de l'entreprise</h3><p class="valeur">{{ $stats['membres'] }}</p></div>
            <ion-icon name="briefcase-outline" class="style_icone"></ion-icon>
        </div>
        <div class="carre_style">
            <div class="contenu_carte"><h3>Factures en attente</h3><p class="valeur">{{ $stats['facturesEnAttente'] }}</p></div>
            <ion-icon name="receipt-outline" class="style_icone"></ion-icon>
        </div>
        <div class="carre_style">
            <div class="contenu_carte"><h3>RDV à venir</h3><p class="valeur">{{ $stats['rdvAVenir'] }}</p></div>
            <ion-icon name="calendar-outline" class="style_icone"></ion-icon>
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
        <a href="{{ route('typesRdv.gestion') }}" class="bouton_choix">
            <ion-icon name="pricetags-outline"></ion-icon> Types de RDV
        </a>
        <a href="{{ route('inscriptionAdmin.Etape1_VU') }}" class="bouton_choix">
            <ion-icon name="person-add-outline"></ion-icon> Ajouter un utilisateur
        </a>
        <a href="{{ route('suppressionAdmin.Etape1_VU') }}" class="bouton_choix">
            <ion-icon name="person-remove-outline"></ion-icon> Supprimer un utilisateur
        </a>
    </div>
</div>
@endsection
