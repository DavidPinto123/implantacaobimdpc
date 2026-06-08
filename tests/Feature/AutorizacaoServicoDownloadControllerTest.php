<?php

use App\Enums\AsStatus;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use Database\Factories\AsEscopoFactory;
use Database\Factories\ObrasFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

it('baixa o pdf da autorizacao de servico', function () {
    $user = createActiveUserWithPermissions(['View:AutorizacaoServico']);
    $this->actingAs($user);

    Config::set('filesystems.media_disk', 'test_media');
    Storage::fake('test_media');

    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => ObrasFactory::new()->create()->id,
        'as_escopo_id' => AsEscopoFactory::new()->create()->id,
        'construtora_id' => Construtora::create([
            'nome' => 'Fornecedor Download AS',
            'cnpj' => '12.000.000/0001-00',
            'tipo' => 'CONSTRUTORA',
        ])->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-DOWNLOAD-001',
        'valor' => 1000,
        'valor_estimado' => 1000,
        'anexo_autorizacao_servico' => 'autorizacao-servico/1/pdf/AS-DOWNLOAD-001.pdf',
    ]);

    Storage::disk('test_media')->put($autorizacaoServico->anexo_autorizacao_servico, 'pdf');

    $this->get(route('autorizacoes-servico.pdf.download', ['record' => $autorizacaoServico]))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=AS-DOWNLOAD-001.pdf');
});
