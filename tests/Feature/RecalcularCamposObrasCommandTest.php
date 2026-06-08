<?php

use App\Models\Obras;
use Carbon\Carbon;
use Database\Factories\ObrasFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

it('recalcula e persiste campos derivados para obras existentes', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00'));

    $obra = ObrasFactory::new()->create([
        'percentual_obra' => 45,
        'percentual_obra_executado' => 58,
        'inicio' => '2026-01-01',
        'fim' => '2026-01-15',
        'inicio_imp' => '2026-01-03',
        'fim_imp' => '2026-01-12',
    ]);

    $obra->projeto->update(['inauguracao' => '2026-01-20']);

    DB::table('obras')
        ->where('id', $obra->id)
        ->update([
            'desvio' => null,
            'dias_para_inauguracao' => null,
            'prazo_planejado' => null,
            'imp_prazo_realiz' => null,
        ]);

    $this->artisan('obras:recalcular-campos')->assertSuccessful();

    /** @var Obras $atualizada */
    $atualizada = Obras::findOrFail($obra->id);

    expect((float) $atualizada->desvio)->toBe(13.0)
        ->and($atualizada->dias_para_inauguracao)->toBe(10)
        ->and($atualizada->prazo_planejado)->toBe(14)
        ->and($atualizada->imp_prazo_realiz)->toBe(17);

    Carbon::setTestNow();
});
