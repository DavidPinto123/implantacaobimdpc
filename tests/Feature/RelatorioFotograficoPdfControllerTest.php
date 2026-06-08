<?php

use App\Models\User;
use App\Services\RelatorioFotograficoPdfService;
use Database\Factories\RelatorioFotograficoFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

it('gera, salva e faz stream do PDF com serviço mockado', function () {
    $this->actingAs(User::factory()->active()->create());

    Config::set('filesystems.media_disk', 'test_media');
    Storage::fake('test_media');

    $record = RelatorioFotograficoFactory::new()->create();

    $fakePdf = new class
    {
        public function output(): string
        {
            return 'PDF-BINARIO-TESTE';
        }
    };

    $this->mock(RelatorioFotograficoPdfService::class, function ($mock) use ($record, $fakePdf) {
        $mock->shouldReceive('makePdf')->once()->withArgs(fn ($arg) => $arg->id === $record->id)->andReturn($fakePdf);
    });

    $expectedPath = RelatorioFotograficoPdfService::pdfStoragePath($record);
    $expectedName = RelatorioFotograficoPdfService::pdfFileName($record);

    $this->get(route('relatorios.pdf', ['record' => $record]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename='.$expectedName);

    expect(Storage::disk('test_media')->exists($expectedPath))->toBeTrue();
});
