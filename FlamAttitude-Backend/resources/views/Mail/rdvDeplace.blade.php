<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif;">
    <h2>Bonjour {{ $pourMembre ? $rdv->membrePrenom : $rdv->clientPrenom }},</h2>
    <p>
        @if($pourMembre)
            Un rendez-vous vous a été assigné ou déplacé. En voici le récapitulatif à jour :
        @else
            Les informations de votre rendez-vous ont changé. En voici le récapitulatif à jour :
        @endif
    </p>
    <table style="border-collapse: collapse;">
        <tr><td style="padding:6px 12px; font-weight:bold;">Type</td><td style="padding:6px 12px; color: {{ $rdv->typeCouleur }}; font-weight:bold;">{{ $rdv->typeNom }}</td></tr>
        <tr><td style="padding:6px 12px; font-weight:bold;">Date</td><td style="padding:6px 12px;">{{ \Illuminate\Support\Carbon::parse($rdv->date)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</td></tr>
        <tr><td style="padding:6px 12px; font-weight:bold;">Horaire</td><td style="padding:6px 12px;">{{ substr($rdv->heure_debut, 0, 5) }} - {{ substr($rdv->heure_fin, 0, 5) }}</td></tr>
        @if($pourMembre)
        <tr><td style="padding:6px 12px; font-weight:bold;">Client</td><td style="padding:6px 12px;">{{ $rdv->clientPrenom }} {{ $rdv->clientNom }}</td></tr>
        @else
        <tr><td style="padding:6px 12px; font-weight:bold;">Avec</td><td style="padding:6px 12px;">{{ $rdv->membrePrenom }} {{ $rdv->membreNom }}</td></tr>
        @endif
        @if($rdv->motif)
        <tr><td style="padding:6px 12px; font-weight:bold; vertical-align:top;">Motif</td><td style="padding:6px 12px;">{{ $rdv->motif }}</td></tr>
        @endif
    </table>
    <p>Pour plus de détails, connectez-vous à votre espace Flam'Attitude.</p>
</body>
</html>
