<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pos-obra:verificar-slas')->everyThirtyMinutes();
Schedule::command('obras:recalcular-campos')->dailyAt('06:00');
Schedule::command('importar:dados')->monthlyOn(1, '03:00')->withoutOverlapping();
