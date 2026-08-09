@extends('layouts.app')

@section('content')
<div class="page-dimension">
    <div class="contenu-principal">

        <div class="accueil">
            <h2 class="titre1">Suppression d'un utilisateur</h2>
        </div>
        <hr id="redline-mdp">

        <form method="GET" action="{{ route('suppressionAdmin.Etape1_VU') }}" class="form_recherche">
            <input type="text" class="barreRecherche" name="search" value="{{ $search }}" placeholder="Rechercher par nom...">
            <button type="submit" class="bouton_barreRecherche">
                <ion-icon name="search-outline"></ion-icon> Rechercher
            </button>
            @if($search)
                <a href="{{ route('suppressionAdmin.Etape1_VU') }}">Réinitialiser</a>
            @endif
        </form>

        <table class="tabInfo">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th style="padding: 10px;">Nom</th>
                    <th style="padding: 10px;">Statut</th>
                    <th style="padding: 10px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($donnees as $d)
                    <tr>
                        <td style="padding: 10px;">{{ $d->prenom }} {{ $d->nom }}</td>
                        <td style="padding: 10px;">{{ $d->grade }}</td>
                        <td style="padding: 10px;">
                            <a href="{{ route('suppressionAdmin.Etape2_VU', ['id' => $d->idUtilisateur]) }}"
                               style="color: #e74c3c; font-weight: bold; text-decoration: none;">
                                <ion-icon name="trash-outline"></ion-icon> Supprimer
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="padding: 20px; text-align: center;">Aucun résultat trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <br><br><br><br><br><br>
    </div>
</div>
@endsection
