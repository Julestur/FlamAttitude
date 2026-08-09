@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/accueilAdmin.css') }}">
<link rel="stylesheet" href="{{ asset('css/accueilStyle.css') }}">

<div class="page-dimension">
    <div class="accueil">
        <h2 class="titre1">{{ $salutation }}, {{ $prenom }} !</h2>
    </div>

    <h2 class="titre2">Mon espace</h2>
    <div class="tab_bord">
        <div class="carre_style">
            <div class="contenu_carte"><h3>Mes prochains RDV</h3><p class="valeur">{{ $stats['prochainsRdv'] }}</p></div>
            <ion-icon name="calendar-outline" class="style_icone"></ion-icon>
        </div>
        <div class="carre_style">
            <div class="contenu_carte"><h3>Factures en attente</h3><p class="valeur">{{ $stats['facturesEnAttente'] }}</p></div>
            <ion-icon name="receipt-outline" class="style_icone"></ion-icon>
        </div>
        <div class="carre_style">
            <div class="contenu_carte"><h3>Mes documents</h3><p class="valeur">{{ $stats['documents'] }}</p></div>
            <ion-icon name="document-text-outline" class="style_icone"></ion-icon>
        </div>
    </div>

    <h2 class="titre2">Actions rapides</h2>
    <div class="barre_choix">
        <a href="{{ route('mon-espace.accueil') }}" class="bouton_choix">
            <ion-icon name="folder-outline"></ion-icon> Mes documents et factures
        </a>
        <a href="{{ route('mon-espace.creneaux') }}" class="bouton_choix">
            <ion-icon name="calendar-outline"></ion-icon> Prendre ou gérer un RDV
        </a>
    </div>
</div>
@endsection
