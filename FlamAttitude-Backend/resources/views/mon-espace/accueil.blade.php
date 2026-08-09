@extends('layouts.app')

@section('content')
<div class="page-dimension">
    <div class="contenu-principal">

        <div class="accueil">
            <h2 class="titre1">Mes documents et factures</h2>
        </div>
        <hr id="redline-mdp">

        <h2 class="titre2">Mes documents</h2>
        <table class="tabInfo">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th style="padding: 10px;">Nom</th>
                    <th style="padding: 10px;">Ajouté le</th>
                    <th style="padding: 10px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $d)
                    <tr>
                        <td style="padding: 10px;">{{ $d->nom }}</td>
                        <td style="padding: 10px;">{{ \Illuminate\Support\Carbon::parse($d->created_at)->format('d/m/Y') }}</td>
                        <td style="padding: 10px;">
                            <a href="{{ asset('storage/' . $d->chemin) }}" target="_blank" style="color: #1E2A3A; font-weight: bold; text-decoration: none;">
                                <ion-icon name="download-outline"></ion-icon> Télécharger
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="padding: 20px; text-align: center;">Aucun document pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>

        <br><br>

        <h2 class="titre2">Mes factures</h2>
        <table class="tabInfo">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th style="padding: 10px;">Description</th>
                    <th style="padding: 10px;">Montant</th>
                    <th style="padding: 10px;">Date</th>
                    <th style="padding: 10px;">Statut</th>
                    <th style="padding: 10px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($factures as $f)
                    <tr>
                        <td style="padding: 10px;">{{ $f->description }}</td>
                        <td style="padding: 10px;">{{ number_format($f->montant, 2, ',', ' ') }} €</td>
                        <td style="padding: 10px;">{{ \Illuminate\Support\Carbon::parse($f->date_emission)->format('d/m/Y') }}</td>
                        <td style="padding: 10px;">
                            @if($f->statut === 'payee')
                                <span style="color: #21a13f; font-weight: bold;">Payée</span>
                            @else
                                <span style="color: #e67e22; font-weight: bold;">En attente</span>
                            @endif
                        </td>
                        <td style="padding: 10px;">
                            @if($f->chemin)
                                <a href="{{ asset('storage/' . $f->chemin) }}" target="_blank" style="color: #1E2A3A; font-weight: bold; text-decoration: none;">
                                    <ion-icon name="download-outline"></ion-icon> PDF
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding: 20px; text-align: center;">Aucune facture pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>

        <br><br><br><br>
    </div>
</div>
@endsection
