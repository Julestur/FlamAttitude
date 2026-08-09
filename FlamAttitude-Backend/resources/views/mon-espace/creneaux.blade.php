@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/edt.css') }}">

<div class="page-dimension">
    <div class="contenu-principal">

        <div class="accueil">
            <h2 class="titre1">Prendre un rendez-vous</h2>
        </div>
        <hr id="redline-mdp">

        @if(session('confirmation'))
            <div style="color: #21a13f; background-color: #d4edda; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; border: 1px solid #c3e6cb;">
                {{ session('confirmation') }}
            </div>
        @endif
        @if(session('erreur'))
            <div style="color: #fa021b; background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;">
                {{ session('erreur') }}
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

        @if($types->isEmpty())
            <p>Aucun type de rendez-vous n'est disponible pour le moment. Merci de réessayer plus tard.</p>
        @else
            <label for="type-select"><strong>Type de rendez-vous :</strong></label>
            <select id="type-select" class="barreRecherche">
                @foreach($types as $t)
                    <option value="{{ $t->idTypeRdv }}" {{ $t->idTypeRdv == $idTypeRdvSelectionne ? 'selected' : '' }}>
                        {{ $t->nom }} ({{ $t->duree_minutes }} min)
                    </option>
                @endforeach
            </select>

            <div class="edt-nav">
                <button type="button" id="edt-semaine-precedente">&laquo; Semaine précédente</button>
                <span id="edt-semaine-label">{{ $labelSemaine }}</span>
                <button type="button" id="edt-semaine-suivante">Semaine suivante &raquo;</button>
            </div>

            @include('partials.edt-grille-client', ['jours' => $jours])
        @endif

        <br><br>

        <h2 class="titre2">Mes rendez-vous</h2>
        <table class="tabInfo">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th style="padding: 10px;">Date</th>
                    <th style="padding: 10px;">Horaire</th>
                    <th style="padding: 10px;">Type</th>
                    <th style="padding: 10px;">Avec</th>
                    <th style="padding: 10px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mesRdv as $r)
                    <tr>
                        <td style="padding: 10px;">{{ \Illuminate\Support\Carbon::parse($r->date)->format('d/m/Y') }}</td>
                        <td style="padding: 10px;">{{ substr($r->heure_debut, 0, 5) }} - {{ substr($r->heure_fin, 0, 5) }}</td>
                        <td style="padding: 10px;"><span style="color: {{ $r->typeCouleur }}; font-weight: bold;">{{ $r->typeNom }}</span></td>
                        <td style="padding: 10px;">{{ $r->membrePrenom }} {{ $r->membreNom }}</td>
                        <td style="padding: 10px;">
                            @if($r->date >= now()->toDateString())
                                <form action="{{ route('mon-espace.creneaux.annuler', $r->idRdv) }}" method="POST" onsubmit="return confirm('Annuler ce rendez-vous ?');">
                                    @csrf
                                    <button type="submit" style="background:none; border:none; color:#e74c3c; font-weight:bold; cursor:pointer;">
                                        <ion-icon name="close-circle-outline"></ion-icon> Annuler
                                    </button>
                                </form>
                            @else
                                <span style="color:#7f8c8d;">Passé</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding: 20px; text-align: center;">Aucun rendez-vous pris pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>

        <br><br><br><br>
    </div>
</div>

<!-- Modale de réservation -->
<div class="edt-modal-fond" id="modal-reservation">
    <div class="edt-modal">
        <h3>Réserver le <span id="modal-date-txt"></span> à <span id="modal-heure-txt"></span></h3>
        <form id="form-reservation" action="{{ route('mon-espace.creneaux.reserver') }}" method="POST">
            @csrf
            <input type="hidden" name="idTypeRdv" id="modal-type" value="">
            <input type="hidden" name="date" id="modal-date" value="">
            <input type="hidden" name="heure" id="modal-heure" value="">
            <textarea name="motif" placeholder="Décrivez votre besoin..." required></textarea>
            <div class="edt-modal-boutons">
                <button type="button" onclick="document.getElementById('modal-reservation').classList.remove('ouvert')">Annuler</button>
                <button type="submit" id="bouton-confirmer-reservation" class="bouton_barreRecherche">Confirmer la réservation</button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/edt.js') }}"></script>
<script>
(function () {
    if (!document.getElementById('type-select')) return;

    let semaine = "{{ $lundi }}";
    const semaineMin = "{{ $lundiMin }}";

    function libelleSemaine(debut) {
        const d1 = new Date(debut + 'T00:00:00');
        const d2 = new Date(d1);
        d2.setDate(d2.getDate() + 6);
        const fmt = function (d) { return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }); };
        return 'Semaine du ' + fmt(d1) + ' au ' + fmt(d2);
    }

    function rechargerGrille() {
        const type = document.getElementById('type-select').value;
        const url = "{{ route('mon-espace.creneaux.grille') }}?type=" + type + "&semaine=" + semaine;
        edtChargerSemaine(url, 'edt-grille-conteneur');
        document.getElementById('edt-semaine-label').textContent = libelleSemaine(semaine);
        document.getElementById('edt-semaine-precedente').disabled = (semaine <= semaineMin);
    }

    function decalerSemaine(jours) {
        const d = new Date(semaine + 'T00:00:00');
        d.setDate(d.getDate() + jours);
        const nouvelle = d.toISOString().slice(0, 10);
        if (nouvelle < semaineMin) return;
        semaine = nouvelle;
        rechargerGrille();
    }

    document.getElementById('edt-semaine-precedente').addEventListener('click', function () { decalerSemaine(-7); });
    document.getElementById('edt-semaine-suivante').addEventListener('click', function () { decalerSemaine(7); });
    document.getElementById('type-select').addEventListener('change', rechargerGrille);
    document.getElementById('edt-semaine-precedente').disabled = (semaine <= semaineMin);

    document.addEventListener('click', function (e) {
        const cell = e.target.closest('.edt-case[data-actionable="reserver"]');
        if (!cell) return;

        document.getElementById('modal-type').value = document.getElementById('type-select').value;
        document.getElementById('modal-date').value = cell.dataset.date;
        document.getElementById('modal-heure').value = cell.dataset.heure;
        document.getElementById('modal-date-txt').textContent = cell.dataset.date;
        document.getElementById('modal-heure-txt').textContent = cell.dataset.heure;
        document.getElementById('modal-reservation').classList.add('ouvert');
    });

    // Empêche un double clic (fréquent sur mobile) d'envoyer deux réservations
    // pour le même créneau, ce qui affichait à tort le message "créneau déjà pris".
    document.getElementById('form-reservation').addEventListener('submit', function () {
        document.getElementById('bouton-confirmer-reservation').disabled = true;
    });
})();
</script>
@endsection
