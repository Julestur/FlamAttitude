<?php

namespace App\Support;

use Illuminate\Http\Request;

class Plateforme
{
    // Marque ajoutée au User-Agent par l'app mobile (voir capacitor.config.json "appendUserAgent").
    private const MARQUEUR_APP = 'FlamAttitudeApp';

    public static function estAppMobile(Request $request): bool
    {
        return str_contains((string) $request->userAgent(), self::MARQUEUR_APP);
    }
}
