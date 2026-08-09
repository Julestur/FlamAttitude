<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class ReglesMotDePasse
{
    /**
     * Politique de mot de passe commune à l'inscription, la création de compte,
     * la réinitialisation et le changement de mot de passe.
     *
     * ->uncompromised() n'est pas activé par défaut car elle appelle l'API
     * publique HaveIBeenPwned à chaque validation ; à activer en production
     * si un accès réseau sortant est garanti.
     */
    public static function politique(): Password
    {
        return Password::min(10)->letters()->mixedCase()->numbers()->symbols();
    }
}
