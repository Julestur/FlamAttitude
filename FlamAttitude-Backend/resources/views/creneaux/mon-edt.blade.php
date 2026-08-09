@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/edt.css') }}">
<link rel="stylesheet" href="{{ asset('css/mon-edt.css') }}">

<div class="page-dimension">
    <div class="contenu-principal">

        <div class="accueil">
            <h2 class="titre1">Mon EDT</h2>
        </div>
        <hr id="redline-mdp">

        @if(session('confirmation'))
            <div style="color: #21a13f; background-color: #d4edda; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; border: 1px solid #c3e6cb;">
                {{ session('confirmation') }}
            </div>
        @endif

        <p class="txtVerif" style="text-align:left;">Vos prochains rendez-vous. Touchez-en un pour voir les détails.</p>

        <div id="mon-edt-liste">
            @forelse($mesRdv as $r)
                <div class="mon-edt-carte"
                     data-idrdv="{{ $r->idRdv }}"
                     data-date="{{ \Illuminate\Support\Carbon::parse($r->date)->translatedFormat('l d F Y') }}"
                     data-horaire="{{ substr($r->heure_debut, 0, 5) }} - {{ substr($r->heure_fin, 0, 5) }}"
                     data-type="{{ $r->typeNom }}"
                     data-couleur="{{ $r->typeCouleur }}"
                     data-client="{{ $r->clientPrenom }} {{ $r->clientNom }}"
                     data-email="{{ $r->clientEmail }}"
                     data-telephone="{{ $r->clientTelephone ?? '' }}"
                     data-adresse="{{ $r->clientAdresse ?? '' }}"
                     data-motif="{{ $r->motif }}"
                >
                    <div class="mon-edt-carte-couleur" style="background-color: {{ $r->typeCouleur }};"></div>
                    <div class="mon-edt-carte-corps">
                        <div class="mon-edt-carte-ligne1">
                            <strong>{{ \Illuminate\Support\Carbon::parse($r->date)->translatedFormat('EEE d MMM') }}</strong>
                            <span>{{ substr($r->heure_debut, 0, 5) }} - {{ substr($r->heure_fin, 0, 5) }}</span>
                        </div>
                        <div class="mon-edt-carte-ligne2">
                            <span class="mon-edt-type" style="color: {{ $r->typeCouleur }};">{{ $r->typeNom }}</span>
                            <span>{{ $r->clientPrenom }} {{ $r->clientNom }}</span>
                        </div>
                    </div>
                    <ion-icon name="chevron-forward-outline"></ion-icon>
                </div>
            @empty
                <p style="text-align:center; padding: 30px 0; color: #7f8c8d;">Aucun rendez-vous à venir.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Modale de détail d'un RDV -->
<div class="edt-modal-fond" id="modal-detail-rdv">
    <div class="edt-modal" id="modal-detail-contenu">
        <h3 id="detail-type" style="margin-top:0;"></h3>
        <p><ion-icon name="calendar-outline"></ion-icon> <span id="detail-date"></span></p>
        <p><ion-icon name="time-outline"></ion-icon> <span id="detail-horaire"></span></p>
        <hr>
        <p><ion-icon name="person-outline"></ion-icon> <strong id="detail-client"></strong></p>
        <p><ion-icon name="mail-outline"></ion-icon> <a id="detail-email" href="#"></a></p>
        <p id="detail-telephone-ligne"><ion-icon name="call-outline"></ion-icon> <a id="detail-telephone" href="#"></a></p>
        <p id="detail-adresse-ligne"><ion-icon name="location-outline"></ion-icon> <span id="detail-adresse"></span></p>
        <p id="detail-motif-ligne"><ion-icon name="chatbox-ellipses-outline"></ion-icon> <span id="detail-motif"></span></p>

        <form id="form-annuler-rdv" method="POST" style="margin-top: 15px;">
            @csrf
            <div class="edt-modal-boutons">
                <button type="button" onclick="document.getElementById('modal-detail-rdv').classList.remove('ouvert')">Fermer</button>
                <button type="submit" style="background:#e74c3c; color:white; border:none; border-radius:5px; padding:8px 16px; cursor:pointer;">
                    <ion-icon name="close-circle-outline"></ion-icon> Annuler le RDV
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const urlAnnulerTemplate = "{{ route('creneaux.rdv.annuler', ['id' => '__ID__']) }}";

    document.querySelectorAll('.mon-edt-carte').forEach(function (carte) {
        carte.addEventListener('click', function () {
            document.getElementById('detail-type').textContent = carte.dataset.type;
            document.getElementById('detail-type').style.color = carte.dataset.couleur;
            document.getElementById('detail-date').textContent = carte.dataset.date;
            document.getElementById('detail-horaire').textContent = carte.dataset.horaire;
            document.getElementById('detail-client').textContent = carte.dataset.client;

            const email = carte.dataset.email || '';
            document.getElementById('detail-email').textContent = email;
            document.getElementById('detail-email').href = 'mailto:' + email;

            const telephone = carte.dataset.telephone || '';
            document.getElementById('detail-telephone-ligne').style.display = telephone ? '' : 'none';
            document.getElementById('detail-telephone').textContent = telephone;
            document.getElementById('detail-telephone').href = 'tel:' + telephone;

            const adresse = carte.dataset.adresse || '';
            document.getElementById('detail-adresse-ligne').style.display = adresse ? '' : 'none';
            document.getElementById('detail-adresse').textContent = adresse;

            const motif = carte.dataset.motif || '';
            document.getElementById('detail-motif-ligne').style.display = motif ? '' : 'none';
            document.getElementById('detail-motif').textContent = motif;

            document.getElementById('form-annuler-rdv').action = urlAnnulerTemplate.replace('__ID__', carte.dataset.idrdv);

            document.getElementById('modal-detail-rdv').classList.add('ouvert');
        });
    });

    document.getElementById('form-annuler-rdv').addEventListener('submit', function (e) {
        if (!confirm('Annuler ce rendez-vous ?')) {
            e.preventDefault();
        }
    });
})();
</script>
@endsection
