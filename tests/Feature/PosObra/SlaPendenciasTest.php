<?php

use App\Enums\PosObra\StatusPendencia;
use App\Events\PosObra\SlaVencido;
use Carbon\Carbon;
use Database\Factories\PendenciaFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;

uses(DatabaseTransactions::class);

it('dispara evento SlaVencido com niveis 1 2 3 e 4', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00'));

    Event::fake([SlaVencido::class]);

    $p1 = PendenciaFactory::new()->create([
        'status' => 'REGISTRADA',
        'data_termino' => Carbon::now()->toDateString(),
    ]);

    $p2 = PendenciaFactory::new()->create([
        'status' => 'REGISTRADA',
        'data_termino' => Carbon::now()->subDay()->toDateString(),
    ]);

    $p3 = PendenciaFactory::new()->create([
        'status' => 'REGISTRADA',
        'data_termino' => Carbon::now()->subDays(2)->toDateString(),
    ]);

    $p4 = PendenciaFactory::new()->create([
        'status' => 'REGISTRADA',
        'data_termino' => Carbon::now()->subDays(3)->toDateString(),
    ]);

    $this->artisan('pos-obra:verificar-slas')->assertSuccessful();

    Event::assertDispatched(SlaVencido::class, 4);
    Event::assertDispatched(SlaVencido::class, fn (SlaVencido $event) => $event->pendencia->is($p1) && $event->nivelEscalamento === 1);
    Event::assertDispatched(SlaVencido::class, fn (SlaVencido $event) => $event->pendencia->is($p2) && $event->nivelEscalamento === 2);
    Event::assertDispatched(SlaVencido::class, fn (SlaVencido $event) => $event->pendencia->is($p3) && $event->nivelEscalamento === 3);
    Event::assertDispatched(SlaVencido::class, fn (SlaVencido $event) => $event->pendencia->is($p4) && $event->nivelEscalamento === 4);

    Carbon::setTestNow();
});

it('nao dispara SlaVencido para pendencias terminais, futuras ou sem data termino', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00'));

    Event::fake([SlaVencido::class]);

    PendenciaFactory::new()->create([
        'status' => StatusPendencia::CONCLUIDA->value,
        'data_termino' => Carbon::now()->subDay()->toDateString(),
    ]);

    PendenciaFactory::new()->create([
        'status' => StatusPendencia::REGISTRADA->value,
        'data_termino' => Carbon::now()->addDay()->toDateString(),
    ]);

    PendenciaFactory::new()->create([
        'status' => StatusPendencia::REGISTRADA->value,
        'data_termino' => null,
    ]);

    $this->artisan('pos-obra:verificar-slas')->assertSuccessful();

    Event::assertNotDispatched(SlaVencido::class);

    Carbon::setTestNow();
});
