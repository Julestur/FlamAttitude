@extends('layouts.app')

@section('content')
<div class="page-dimension">
    <div class="contenu-principal">

        <div class="accueil">
            <h2 class="titre1">Rechercher un client</h2>
        </div>
        <hr id="redline-mdp">

        <form method="GET" action="{{ route('clients.recherche') }}" class="form_recherche">
            <input type="text" class="barreRecherche" name="search" value="{{ $search }}" placeholder="Rechercher par nom, prénom ou email...">
            <button type="submit" class="bouton_barreRecherche">
                <ion-icon name="search-outline"></ion-icon> Rechercher
            </button>
            @if($search)
                <a href="{{ route('clients.recherche') }}">Réinitialiser</a>
            @endif
        </form>

        <table class="tabInfo">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th style="padding: 10px;">Nom</th>
                    <th style="padding: 10px;">Email</th>
                    <th style="padding: 10px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $c)
                    <tr>
                        <td style="padding: 10px;">{{ $c->prenom }} {{ $c->nom }}</td>
                        <td style="padding: 10px;">{{ $c->email }}</td>
                        <td style="padding: 10px;">
                            <a href="{{ route('clients.dossier', $c->idUtilisateur) }}" style="color: #1E2A3A; font-weight: bold; text-decoration: none;">
                                <ion-icon name="folder-open-outline"></ion-icon> Voir le dossier
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="padding: 20px; text-align: center;">Aucun client trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>

        <br><br><br><br>
    </div>
</div>
@endsection
