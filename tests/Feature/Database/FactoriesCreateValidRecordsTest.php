<?php

use App\Models\Obras;
use App\Models\Projeto;
use Database\Factories\AutorizacaoServicoFactory;
use Database\Factories\CidadeFactory;
use Database\Factories\ConfiguracaoSlaFactory;
use Database\Factories\ConstrutoraFactory;
use Database\Factories\ControleNotaFiscalItemFactory;
use Database\Factories\ControleNotaFiscalNotaFactory;
use Database\Factories\ControlePedidoFactory;
use Database\Factories\DisciplinaConfigFactory;
use Database\Factories\EstadoFactory;
use Database\Factories\EtapaFactory;
use Database\Factories\PaisFactory;
use Database\Factories\PendenciaFactory;
use Database\Factories\SetorFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('cria registros validos com as factories centrais da suite', function () {
    $pais = PaisFactory::new()->create();
    $estado = EstadoFactory::new()->create(['pais_id' => $pais->id]);
    $cidade = CidadeFactory::new()->create(['estado_id' => $estado->id]);
    $etapa = EtapaFactory::new()->create();
    $setor = SetorFactory::new()->create();
    $construtora = ConstrutoraFactory::new()->create();

    $projeto = Projeto::factory()->create([
        'etapa_id' => $etapa->id,
        'cidade_id' => $cidade->id,
        'estado_id' => $estado->id,
        'pais_id' => $pais->id,
    ]);

    $obra = Obras::factory()->create(['projeto_id' => $projeto->id]);
    $controlePedido = ControlePedidoFactory::new()->create(['projeto_id' => $projeto->id, 'construtora_id' => $construtora->id]);
    $controleNotaFiscal = $obra->controlesNotaFiscal()->firstOrFail();
    $itemNotaFiscal = ControleNotaFiscalItemFactory::new()->create(['controle_nota_fiscal_id' => $controleNotaFiscal->id]);
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'controle_nota_fiscal_item_id' => $itemNotaFiscal->id,
    ]);
    $notaFiscal = ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => $autorizacaoServico->id,
        'autorizacao_servico_adicional_id' => null,
    ]);
    $disciplina = DisciplinaConfigFactory::new()->create();
    $configuracaoSla = ConfiguracaoSlaFactory::new()->create();
    $pendencia = PendenciaFactory::new()->create([
        'obras_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'disciplina_config_id' => $disciplina->id,
    ]);

    foreach ([
        $pais,
        $estado,
        $cidade,
        $etapa,
        $setor,
        $construtora,
        $projeto,
        $obra,
        $controlePedido,
        $controleNotaFiscal,
        $itemNotaFiscal,
        $autorizacaoServico,
        $notaFiscal,
        $disciplina,
        $configuracaoSla,
        $pendencia,
    ] as $model) {
        $this->assertModelExists($model);
    }
});
