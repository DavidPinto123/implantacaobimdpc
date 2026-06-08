<?php

use App\Filament\Resources\CapexSimulacaos\Pages\EditCapexSimulacao;
use App\Services\CapexSimulacaoPdfService;
use Database\Factories\CapexSimulacaoFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('exportar_pdf retorna stream de download com serviço mockado', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:CapexSimulacao',
        'View:CapexSimulacao',
        'Update:CapexSimulacao',
    ]);

    $this->actingAs($user);

    $record = CapexSimulacaoFactory::new()->create();

    $fakePdf = new class
    {
        public function output(): string
        {
            return 'PDF-CAPEX-TESTE';
        }
    };

    $this->mock(CapexSimulacaoPdfService::class, function ($mock) use ($fakePdf) {
        $mock->shouldReceive('makePdf')->once()->andReturn($fakePdf);
        $mock->shouldReceive('nomeArquivo')->once()->andReturn('capex-teste.pdf');
    });

    Livewire::test(EditCapexSimulacao::class, ['record' => $record->getRouteKey()])
        ->callAction('exportar_pdf')
        ->assertFileDownloaded('capex-teste.pdf');
});
