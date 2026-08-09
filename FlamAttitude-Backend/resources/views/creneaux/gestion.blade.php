@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/edt.css') }}">

<div class="page-dimension">
    <div class="contenu-principal">

        <div class="accueil">
            <h2 class="titre1">Mes disponibilités</h2>
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

        <p class="txtVerif" style="text-align:left;">
            Cliquez sur une case pour vous rendre disponible, cliquez à nouveau pour retirer. Les cases colorées sont déjà réservées (cliquez pour annuler le RDV).
        </p>

        <div class="edt-nav">
            <button type="button" id="edt-semaine-precedente">&laquo; Semaine précédente</button>
            <span id="edt-semaine-label">{{ $labelSemaine }}</span>
            <button type="button" id="edt-semaine-suivante">Semaine suivante &raquo;</button>
        </div>

        @include('partials.edt-grille-membre', ['jours' => $jours])

        <p class="txtVerif" style="text-align:left; margin-top: 20px;">
            Pour voir le détail de vos rendez-vous (client, contact, adresse, motif), direction l'onglet <strong>Mon EDT</strong>.
        </p>

        <br><br>
    </div>
</div>

<script src="{{ asset('js/edt.js') }}"></script>
<script>
(function () {
    let semaine = "{{ $lundi }}";
    const semaineMin = "{{ $lundiMin }}";
    const urlAnnulerTemplate = "{{ route('creneaux.rdv.annuler', ['id' => '__ID__']) }}";

    function libelleSemaine(debut) {
        const d1 = new Date(debut + 'T00:00:00');
        const d2 = new Date(d1);
        d2.setDate(d2.getDate() + 6);
        const fmt = function (d) { return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }); };
        return 'Semaine du ' + fmt(d1) + ' au ' + fmt(d2);
    }

    function rechargerGrille() {
        const url = "{{ route('creneaux.grille') }}?semaine=" + semaine;
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
    document.getElementById('edt-semaine-precedente').disabled = (semaine <= semaineMin);

    document.addEventListener('click', function (e) {
        const caseToggle = e.target.closest('.edt-case[data-actionable="toggle"]');
        if (caseToggle) {
            const donnees = new FormData();
            donnees.append('_token', '{{ csrf_token() }}');
            donnees.append('date', caseToggle.dataset.date);
            donnees.append('heure', caseToggle.dataset.heure);
            donnees.append('semaine', semaine);

            fetch("{{ route('creneaux.toggle') }}", {
                method: 'POST',
                body: donnees,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    document.getElementById('edt-grille-conteneur').outerHTML = html;
                })
                .catch(function (err) { console.error('Erreur toggle EDT :', err); });

            return;
        }

        const caseReserve = e.target.closest('.edt-case[data-actionable="annuler"]');
        if (caseReserve) {
            if (!confirm('Annuler ce rendez-vous ?')) return;

            const donnees = new FormData();
            donnees.append('_token', '{{ csrf_token() }}');

            fetch(urlAnnulerTemplate.replace('__ID__', caseReserve.dataset.rdv), {
                method: 'POST',
                body: donnees,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).then(function () { window.location.reload(); });
        }
    });
})();
</script>
@endsection
