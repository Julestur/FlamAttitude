@extends('layouts.app')

@section('content')
<div class="page-dimension">
    <div class="contenu-principal">

        <div class="accueil">
            <h2 class="titre1">Types de rendez-vous</h2>
        </div>
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

        @forelse($types as $t)
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap; padding: 12px; border-bottom: 1px solid #e0e0e0;">

                <form action="{{ route('typesRdv.modifier', $t->idTypeRdv) }}" method="POST" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
                    @csrf
                    <input type="color" name="couleur" value="{{ $t->couleur }}" style="width: 40px; height: 30px; border: none; padding: 0;">
                    <input type="text" name="nom" value="{{ $t->nom }}" class="barreRecherche" style="width: 160px;" required>
                    <input type="number" name="duree_minutes" value="{{ $t->duree_minutes }}" min="5" max="600" class="barreRecherche" style="width: 90px;" required> min
                    <button type="submit" class="bouton_barreRecherche">Enregistrer</button>
                </form>

                <span style="min-width: 90px; text-align:center;">
                    @if($t->actif)
                        <span style="color: #21a13f; font-weight: bold;">Actif</span>
                    @else
                        <span style="color: #7f8c8d; font-weight: bold;">Désactivé</span>
                    @endif
                </span>

                <form action="{{ route('typesRdv.basculerActif', $t->idTypeRdv) }}" method="POST">
                    @csrf
                    <button type="submit" style="background:none; border:1px solid #ccc; border-radius:5px; padding:8px 12px; cursor:pointer;">
                        {{ $t->actif ? 'Désactiver' : 'Réactiver' }}
                    </button>
                </form>
            </div>
        @empty
            <p style="padding: 20px; text-align: center;">Aucun type de RDV pour le moment.</p>
        @endforelse

        <br><br>

        <h2 class="titre2">Ajouter un type de RDV</h2>
        <form action="{{ route('typesRdv.creer') }}" method="POST" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            @csrf
            <input type="text" name="nom" placeholder="Nom (ex: Maintenance)" class="barreRecherche" required>
            <input type="number" name="duree_minutes" placeholder="Durée (min)" min="5" max="600" class="barreRecherche" style="width: 120px;" required>
            <label>Couleur : <input type="color" name="couleur" value="#D9531E" style="width: 40px; height: 30px; border: none; padding: 0;"></label>
            <button type="submit" class="bouton_barreRecherche">
                <ion-icon name="add-outline"></ion-icon> Ajouter
            </button>
        </form>

        <br><br>

        <h2 class="titre2">Quels employés font quels RDV ?</h2>
        @if($membres->isEmpty())
            <p>Aucun employé pour le moment.</p>
        @elseif($types->isEmpty())
            <p>Créez d'abord un type de RDV ci-dessus.</p>
        @else
            <form action="{{ route('typesRdv.affectations') }}" method="POST">
                @csrf
                <div style="overflow-x:auto;">
                <table class="tabInfo">
                    <thead>
                        <tr style="background-color: #f4f4f4;">
                            <th style="padding: 10px;">Employé</th>
                            @foreach($types as $t)
                                <th style="padding: 10px; color: {{ $t->couleur }};">{{ $t->nom }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($membres as $m)
                            <tr>
                                <td style="padding: 10px;">{{ $m->prenom }} {{ $m->nom }}</td>
                                @foreach($types as $t)
                                    <td style="padding: 10px; text-align: center;">
                                        <input type="checkbox"
                                               name="types[{{ $m->idUtilisateur }}][]"
                                               value="{{ $t->idTypeRdv }}"
                                               {{ in_array($t->idTypeRdv, $affectations[$m->idUtilisateur] ?? []) ? 'checked' : '' }}>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <br>
                <button type="submit" class="bouton_barreRecherche">Enregistrer les affectations</button>
            </form>
        @endif

        <br><br><br><br>
    </div>
</div>
@endsection
