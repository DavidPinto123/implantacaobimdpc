<?php

use App\Filament\Resources\ProjetoResource\Pages\EditProjeto;
use App\Models\Projeto;
use App\Models\User;
use App\Support\Cnpj;
use Database\Seeders\LocalDemoSeeder;
use Database\Seeders\LocalUserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

it('semeia as etapas usadas pelo fluxo comercial de projetos', function () {
    $seeder = app(LocalDemoSeeder::class);

    $method = new ReflectionMethod($seeder, 'seedReferenceData');
    $method->setAccessible(true);
    $method->invoke($seeder);

    expect(DB::table('etapas')->where('nome', 'Prospecção')->exists())->toBeTrue()
        ->and(DB::table('etapas')->where('nome', 'Reunião de comitê')->exists())->toBeTrue()
        ->and(DB::table('etapas')->where('nome', 'Viabilidade')->exists())->toBeTrue()
        ->and(DB::table('etapas')->where('nome', 'Briefing e Layout')->exists())->toBeTrue()
        ->and(DB::table('etapas')->where('nome', 'Ordem de investimento')->exists())->toBeTrue()
        ->and(DB::table('etapas')->where('nome', 'Contrato')->exists())->toBeTrue()
        ->and(DB::table('etapas')->where('nome', 'Projetos de obra')->exists())->toBeTrue()
        ->and(DB::table('etapas')->where('nome', 'Orçamentos e equalização')->exists())->toBeTrue()
        ->and(DB::table('estados')->where('nome', 'São Paulo')->value('uf'))->toBe('SP');
});

it('semeia projetos demo com cnpjs válidos e status fiscal coerente', function () {
    User::factory()->active()->create(['email' => 'gestor.obra@example.test']);

    $seeder = app(LocalDemoSeeder::class);

    foreach (['seedReferenceData', 'seedProjetosDemo'] as $methodName) {
        $method = new ReflectionMethod($seeder, $methodName);
        $method->setAccessible(true);
        $method->invoke($seeder);
    }

    $projetos = DB::table('projetos')
        ->where('sigla', 'like', 'DEMO-PJT-%')
        ->orderBy('sigla')
        ->get(['id', 'sigla', 'sigla_antiga', 'cnpj', 'cnpj_provisorio', 'status_cnpj', 'data_posse', 'tipo_imovel', 'projeto_croqui']);

    expect($projetos)->toHaveCount(5)
        ->and($projetos->pluck('sigla_antiga')->filter())->toHaveCount(5)
        ->and($projetos->pluck('data_posse')->filter())->toHaveCount(5)
        ->and($projetos->pluck('tipo_imovel')->unique()->values()->all())->toBe(['padrao'])
        ->and($projetos->pluck('projeto_croqui')->filter())->toBeEmpty()
        ->and($projetos->where('status_cnpj', 'definitivo'))->toHaveCount(3)
        ->and($projetos->where('status_cnpj', 'provisorio'))->toHaveCount(2)
        ->and($projetos->where('status_cnpj', 'provisorio')->pluck('cnpj_provisorio')->filter())->toHaveCount(2)
        ->and($projetos->where('status_cnpj', 'definitivo')->pluck('cnpj')->filter())->toHaveCount(3)
        ->and($projetos->where('status_cnpj', 'definitivo')->pluck('cnpj_provisorio')->filter())->toBeEmpty();

    $documentos = $projetos
        ->flatMap(fn (object $projeto): array => array_filter([$projeto->cnpj, $projeto->cnpj_provisorio]))
        ->values();

    expect($documentos->contains(fn (string $documento): bool => preg_match('/[A-Z]/', $documento) === 1))->toBeTrue()
        ->and($documentos->contains(fn (string $documento): bool => preg_match('/^[0-9.\/-]+$/', $documento) === 1))->toBeTrue();

    foreach ($documentos as $documento) {
        expect(Cnpj::isValid($documento))->toBeTrue();
    }
});

it('semeia usuarios compativeis com os selects de squad do projeto', function () {
    $this->app->detectEnvironment(fn (): string => 'local');

    app(LocalUserSeeder::class)->run();

    $gestorObra = User::query()->where('email', 'gestor.obra@example.test')->firstOrFail();
    $pmo = User::query()->where('email', 'pmo.expansao@example.test')->firstOrFail();

    expect(User::role('Comercial')->where('email', 'comercial.expansao@example.test')->exists())->toBeTrue()
        ->and(User::role('PMO')->where('email', $pmo->email)->exists())->toBeTrue()
        ->and(User::role('Engenharia')->where('email', $gestorObra->email)->exists())->toBeTrue()
        ->and(User::role('Arquitetura')->where('email', $gestorObra->email)->exists())->toBeTrue()
        ->and($gestorObra->hasRole('Gestor'))->toBeTrue();
});

it('permite salvar o squad em projeto demo local', function () {
    $this->app->detectEnvironment(fn (): string => 'local');

    app(LocalUserSeeder::class)->run();

    $seeder = app(LocalDemoSeeder::class);

    foreach (['seedReferenceData', 'seedProjetosDemo'] as $methodName) {
        $method = new ReflectionMethod($seeder, $methodName);
        $method->setAccessible(true);
        $method->invoke($seeder);
    }

    $superAdmin = User::query()->where('email', 'super.admin@example.test')->firstOrFail();
    $pmo = User::role('PMO')->firstOrFail();
    $comercial = User::role('Comercial')->firstOrFail();
    $gestorObra = User::query()->where('email', 'gestor.obra@example.test')->firstOrFail();
    $projeto = Projeto::query()->where('sigla', 'DEMO-PJT-001')->firstOrFail();

    $this->actingAs($superAdmin);

    Livewire::test(EditProjeto::class, ['record' => $projeto->getRouteKey()])
        ->set('data.resp_pmo', $pmo->id)
        ->set('data.resp_com', $comercial->id)
        ->set('data.resp_arq', $gestorObra->id)
        ->set('data.resp_eng', $gestorObra->id)
        ->call('save')
        ->assertHasNoFormErrors();

    $projeto->refresh();

    expect($projeto->resp_pmo)->toBe($pmo->id)
        ->and($projeto->resp_com)->toBe($comercial->id)
        ->and($projeto->resp_arq)->toBe($gestorObra->id)
        ->and($projeto->resp_eng)->toBe($gestorObra->id);
});
