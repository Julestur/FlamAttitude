<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rappels de RDV par email : tous les jours à 8h (1 semaine avant + veille du RDV)
Schedule::command('rdv:rappels')->dailyAt('08:00');
