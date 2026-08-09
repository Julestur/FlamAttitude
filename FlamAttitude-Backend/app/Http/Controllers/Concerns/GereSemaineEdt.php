<?php

namespace App\Http\Controllers\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

trait GereSemaineEdt
{
    /**
     * Lundi de la semaine demandée (paramètre "semaine"), jamais avant la semaine en cours.
     */
    private function lundiDepuisRequete(Request $request): CarbonImmutable
    {
        $semaine = $request->query('semaine') ?? $request->input('semaine');
        $lundi = ($semaine ? CarbonImmutable::parse($semaine) : CarbonImmutable::now())->startOfWeek(1);

        return $lundi->max($this->lundiCourant());
    }

    private function lundiCourant(): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfWeek(1);
    }

    private function labelSemaine(CarbonImmutable $lundi): string
    {
        return 'Semaine du '.$lundi->format('d/m/Y').' au '.$lundi->addDays(6)->format('d/m/Y');
    }
}
