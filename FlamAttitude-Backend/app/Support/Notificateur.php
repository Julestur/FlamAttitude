<?php

namespace App\Support;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

/**
 * Envoi de notifications push (Firebase Cloud Messaging), en complément des
 * emails déjà envoyés pour les mêmes évènements (RDV confirmé/déplacé/annulé).
 * Best-effort : un échec d'envoi (jeton absent/périmé) ne doit jamais faire
 * échouer l'action métier qui l'a déclenché.
 */
class Notificateur
{
    public static function envoyer(?string $jetonFcm, string $titre, string $corps): void
    {
        if (! $jetonFcm) {
            return;
        }

        try {
            $message = CloudMessage::new()
                ->withToken($jetonFcm)
                ->withNotification(Notification::create($titre, $corps));

            Firebase::messaging()->send($message);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
