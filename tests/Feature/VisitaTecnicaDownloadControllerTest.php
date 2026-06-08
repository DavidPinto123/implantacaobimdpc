<?php

use App\Jobs\GenerateVisitaTecnicaPdfJob;
use App\Models\User;
use App\Services\VisitaTecnicaPdfService;
use Database\Factories\RelatorioVisitaTecnicaFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

it('baixa o PDF quando já existe armazenamento válido', function () {
    $this->actingAs(User::factory()->active()->create());

    Config::set('filesystems.media_disk', 'test_media');
    Storage::fake('test_media');

    $record = RelatorioVisitaTecnicaFactory::new()->create([
        'numero_relatorio_vt' => 'VT-1234',
        'pdf_path' => 'relatorios-vt/VT-1234/pdf/Relatorio-Visita-Tecnica-VT-1234.pdf',
    ]);

    Storage::disk('test_media')->put($record->pdf_path, 'pdf-conteudo');

    $this->mock(VisitaTecnicaPdfService::class, function ($mock) use ($record) {
        $mock->shouldReceive('hasValidStoredPdf')->once()->withArgs(fn ($arg) => $arg->id === $record->id)->andReturnTrue();
    });

    $expectedName = 'Relatorio-Visita-Tecnica-'.$record->fresh()->numero_relatorio_vt.'.pdf';

    $this->get(route('download.visita.tecnica', ['record' => $record]))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename='.$expectedName);
});

it('enfileira job e redireciona quando PDF está ausente', function () {
    $this->actingAs(User::factory()->active()->create());

    Bus::fake();

    $record = RelatorioVisitaTecnicaFactory::new()->create();

    $this->mock(VisitaTecnicaPdfService::class, function ($mock) use ($record) {
        $mock->shouldReceive('hasValidStoredPdf')->once()->andReturnFalse();
        $mock->shouldReceive('isGenerating')->once()->andReturnFalse();
        $mock->shouldReceive('markAsGenerating')->once()->withArgs(fn ($arg) => $arg->id === $record->id);
    });

    $this->from('/anterior')
        ->get(route('download.visita.tecnica', ['record' => $record]))
        ->assertRedirect('/anterior');

    Bus::assertDispatched(GenerateVisitaTecnicaPdfJob::class, fn (GenerateVisitaTecnicaPdfJob $job) => $job->recordId === $record->id);
});

it('não enfileira nova geração quando o PDF já está em geração', function () {
    $this->actingAs(User::factory()->active()->create());

    Bus::fake();

    $record = RelatorioVisitaTecnicaFactory::new()->create();

    $this->mock(VisitaTecnicaPdfService::class, function ($mock) {
        $mock->shouldReceive('hasValidStoredPdf')->once()->andReturnFalse();
        $mock->shouldReceive('isGenerating')->once()->andReturnTrue();
        $mock->shouldReceive('markAsGenerating')->never();
    });

    $this->from('/anterior')
        ->get(route('download.visita.tecnica', ['record' => $record]))
        ->assertRedirect('/anterior');

    Bus::assertNotDispatched(GenerateVisitaTecnicaPdfJob::class);
});
