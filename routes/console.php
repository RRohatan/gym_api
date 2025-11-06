<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // <-- 1. IMPORTAR SCHEDULE

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// --- 2. AÑADIR LA TAREA PROGRAMADA ---
// Esto le dice a Laravel que ejecute tu comando de suspensión/notificación
// una vez al día, a medianoche.
Schedule::command('app:update-membership-status')->daily();
