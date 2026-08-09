<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif;">
    <h2>Bonjour {{ $rdv->clientPrenom }},</h2>
    <p>Votre rendez-vous a bien été confirmé :</p>
    <table style="border-collapse: collapse;">
        <tr><td style="padding:6px 12px; font-weight:bold;">Type</td><td style="padding:6px 12px; color: {{ $rdv->typeCouleur }}; font-weight:bold;">{{ $rdv->typeNom }}</td></tr>
        <tr><td style="padding:6px 12px; font-weight:bold;">Date</td><td style="padding:6px 12px;">{{ \Illuminate\Support\Carbon::parse($rdv->date)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</td></tr>
        <tr><td style="padding:6px 12px; font-weight:bold;">Horaire</td><td style="padding:6px 12px;">{{ substr($rdv->heure_debut, 0, 5) }} - {{ substr($rdv->heure_fin, 0, 5) }}</td></tr>
        <tr><td style="padding:6px 12px; font-weight:bold;">Avec</td><td style="padding:6px 12px;">{{ $rdv->membrePrenom }} {{ $rdv->membreNom }}</td></tr>
        @if($rdv->motif)
        <tr><td style="padding:6px 12px; font-weight:bold; vertical-align:top;">Motif</td><td style="padding:6px 12px;">{{ $rdv->motif }}</td></tr>
        @endif
    </table>
    <p>Vous recevrez un rappel par email 1 semaine avant et la veille de ce rendez-vous.</p>
    <p>Pour annuler ou consulter vos rendez-vous, connectez-vous à votre espace Flam'Attitude.</p>
</body>
</html>
