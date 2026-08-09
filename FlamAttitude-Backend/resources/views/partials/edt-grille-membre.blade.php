<div class="edt-grille-scroll">
<div class="edt-grille" id="edt-grille-conteneur">
    <div class="edt-entete edt-cellule-heure"></div>
    @foreach($jours as $j)
        <div class="edt-entete">{{ ucfirst($j['jour']->locale('fr')->isoFormat('ddd DD/MM')) }}</div>
    @endforeach

    @foreach(\App\Support\Planning::heuresGrille() as $heure)
        <div class="edt-cellule-heure">{{ $heure }}</div>
        @foreach($jours as $j)
            @php $case = $j['creneaux'][$heure]; @endphp

            @if($case['etat'] === 'reserve')
                <div class="edt-case reserve"
                     style="background-color: {{ $case['rdv']->typeCouleur }};"
                     data-actionable="annuler"
                     data-rdv="{{ $case['rdv']->idRdv }}"
                     title="{{ $case['rdv']->typeNom }} — {{ $case['rdv']->clientPrenom }} {{ $case['rdv']->clientNom }} — cliquer pour annuler"
                >{{ $case['rdv']->clientPrenom }}</div>
            @elseif($case['etat'] === 'disponible')
                <div class="edt-case dispo"
                     data-actionable="toggle"
                     data-date="{{ $j['date'] }}"
                     data-heure="{{ $heure }}"
                     title="Disponible — cliquer pour retirer"
                ></div>
            @else
                <div class="edt-case vide"
                     data-actionable="toggle"
                     data-date="{{ $j['date'] }}"
                     data-heure="{{ $heure }}"
                     title="Indisponible — cliquer pour se rendre disponible"
                ></div>
            @endif
        @endforeach
    @endforeach
</div>
</div>
