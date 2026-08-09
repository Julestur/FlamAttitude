<div class="edt-grille-scroll">
<div class="edt-grille" id="edt-grille-conteneur">
    <div class="edt-entete edt-cellule-heure"></div>
    @foreach($jours as $j)
        <div class="edt-entete">{{ ucfirst($j['jour']->locale('fr')->isoFormat('ddd DD/MM')) }}</div>
    @endforeach

    @foreach(\App\Support\Planning::heuresGrille() as $heure)
        <div class="edt-cellule-heure">{{ $heure }}</div>
        @foreach($jours as $j)
            @php $dispo = $j['creneaux'][$heure]; @endphp
            <div class="edt-case {{ $dispo ? 'libre' : 'indispo' }}"
                 @if($dispo)
                     data-actionable="reserver"
                     data-date="{{ $j['date'] }}"
                     data-heure="{{ $heure }}"
                     title="Cliquer pour réserver"
                 @endif
            ></div>
        @endforeach
    @endforeach
</div>
</div>
