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

// WhatsApp: notifica tarefas que venceram ontem (toda manhã às 8h)
Schedule::command('whatsapp:notificar-atrasos')->dailyAt('08:00');

// WhatsApp: resumo semanal toda segunda-feira às 9h
Schedule::command('whatsapp:agenda-semanal')->weeklyOn(1, '09:00');

// Email: resumo semanal de planejamentos para cada Gerente Geral (segunda-feira às 8h30)
Schedule::command('resumo:semanal')->weeklyOn(1, '08:30');
