@extends('layouts.app')

@section('content')
<div class="page-dimension">
    <div class="contenu-principal">

        <div class="accueil">
            <h2 class="titre1">Dossier de {{ $client->prenom }} {{ $client->nom }}</h2>
        </div>
        <p class="txtVerif">{{ $client->email }} — identifiant : {{ $client->identifiant }}</p>
        <hr id="redline-mdp">

        @if(session('confirmation'))
            <div style="color: #21a13f; background-color: #d4edda; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; border: 1px solid #c3e6cb;">
                {{ session('confirmation') }}
            </div>
        @endif
        @if ($errors->any())
            <div style="color: #fa021b; background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;">
                <ul style="margin: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- COORDONNÉES -->
        <h2 class="titre2">Coordonnées</h2>
        <form action="{{ route('clients.contact.modifier', $client->idUtilisateur) }}" method="POST" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 20px;">
            @csrf
            <input type="tel" name="telephone" placeholder="Téléphone" class="barreRecherche" value="{{ $client->telephone }}">
            <input type="text" name="adresse" placeholder="Adresse" class="barreRecherche" style="flex: 1; min-width: 220px;" value="{{ $client->adresse }}">
            <button type="submit" class="bouton_barreRecherche">
                <ion-icon name="save-outline"></ion-icon> Enregistrer
            </button>
        </form>

        <!-- DOCUMENTS -->
        <h2 class="titre2">Documents</h2>
        <table class="tabInfo">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th style="padding: 10px;">Nom</th>
                    <th style="padding: 10px;">Ajouté par</th>
                    <th style="padding: 10px;">Le</th>
                    <th style="padding: 10px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $d)
                    <tr>
                        <td style="padding: 10px;">{{ $d->nom }}</td>
                        <td style="padding: 10px;">{{ $d->ajoutePrenom ? $d->ajoutePrenom . ' ' . $d->ajouteNom : '—' }}</td>
                        <td style="padding: 10px;">{{ \Illuminate\Support\Carbon::parse($d->created_at)->format('d/m/Y') }}</td>
                        <td style="padding: 10px;">
                            <a href="{{ asset('storage/' . $d->chemin) }}" target="_blank" style="color: #1E2A3A; font-weight: bold; text-decoration: none;">
                                <ion-icon name="download-outline"></ion-icon> Télécharger
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="padding: 20px; text-align: center;">Aucun document.</td></tr>
                @endforelse
            </tbody>
        </table>

        <form action="{{ route('clients.documents.ajouter', $client->idUtilisateur) }}" method="POST" enctype="multipart/form-data" style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            @csrf
            <input type="text" name="nom" placeholder="Nom du document" class="barreRecherche" required>
            <input type="file" name="fichier" required>
            <button type="submit" class="bouton_barreRecherche">
                <ion-icon name="add-outline"></ion-icon> Ajouter le document
            </button>
        </form>

        <br><br>

        <!-- FACTURES -->
        <h2 class="titre2">Factures</h2>
        <table class="tabInfo">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th style="padding: 10px;">Description</th>
                    <th style="padding: 10px;">Montant</th>
                    <th style="padding: 10px;">Date</th>
                    <th style="padding: 10px;">Statut</th>
                    <th style="padding: 10px;">PDF</th>
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
                                <a href="{{ asset('storage/' . $f->chemin) }}" target="_blank" style="color: #1E2A3A;">
                                    <ion-icon name="download-outline"></ion-icon>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding: 20px; text-align: center;">Aucune facture.</td></tr>
                @endforelse
            </tbody>
        </table>

        <form action="{{ route('clients.factures.ajouter', $client->idUtilisateur) }}" method="POST" enctype="multipart/form-data" style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            @csrf
            <input type="text" name="description" placeholder="Description" class="barreRecherche" required>
            <input type="number" name="montant" placeholder="Montant (€)" step="0.01" min="0" class="barreRecherche" style="max-width: 120px;" required>
            <input type="date" name="date_emission" class="barreRecherche" value="{{ date('Y-m-d') }}" required>
            <select name="statut" class="barreRecherche" required>
                <option value="en_attente">En attente</option>
                <option value="payee">Payée</option>
            </select>
            <input type="file" name="fichier" accept="application/pdf">
            <button type="submit" class="bouton_barreRecherche">
                <ion-icon name="add-outline"></ion-icon> Ajouter la facture
            </button>
        </form>

        <br><br>

        <!-- HISTORIQUE DES RENDEZ-VOUS -->
        <h2 class="titre2">Historique des rendez-vous</h2>
        <table class="tabInfo">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th style="padding: 10px;">Date</th>
                    <th style="padding: 10px;">Horaire</th>
                    <th style="padding: 10px;">Type</th>
                    <th style="padding: 10px;">Avec</th>
                    <th style="padding: 10px;">Motif</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rdv as $r)
                    <tr>
                        <td style="padding: 10px;">{{ \Illuminate\Support\Carbon::parse($r->date)->format('d/m/Y') }}</td>
                        <td style="padding: 10px;">{{ substr($r->heure_debut, 0, 5) }} - {{ substr($r->heure_fin, 0, 5) }}</td>
                        <td style="padding: 10px;"><span style="color: {{ $r->typeCouleur }}; font-weight: bold;">{{ $r->typeNom }}</span></td>
                        <td style="padding: 10px;">{{ $r->membrePrenom }} {{ $r->membreNom }}</td>
                        <td style="padding: 10px;">{{ $r->motif }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding: 20px; text-align: center;">Aucun rendez-vous.</td></tr>
                @endforelse
            </tbody>
        </table>

        <br><br><br><br>
    </div>
</div>
@endsection
