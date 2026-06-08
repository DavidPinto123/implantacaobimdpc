<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Pages\FinanceiroNotasFiscais;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use Database\Factories\AsaFactory;
use Database\Factories\AutorizacaoServicoFactory;
use Database\Factories\ControleNotaFiscalNotaFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTransactions::class);

function financeiroNotasFiscaisPageProbe(): object
{
    return new class extends FinanceiroNotasFiscais
    {
        /**
         * @return array<int, string>
         */
        public function unidadesOrdenadas(): array
        {
            return $this->unidadesOrdenadasPorPendentes();
        }

        /**
         * @return Collection<int, ControleNotaFiscalNota>
         */
        public function notasAprovadasDaTabela(): Collection
        {
            return $this->tableExcelPage()->buildQuery()->get();
        }
    };
}

/**
 * @return array{0: Obras, 1: ControleNotaFiscal}
 */
function createControleFinanceiroNotasFiscais(string $unidade): array
{
    $obra = Obras::factory()->create(['unidade' => $unidade]);

    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
    ]);

    return [$obra, $controle];
}

function createNotaFinanceiroNotasFiscaisParaItem(ControleNotaFiscal $controle, Obras $obra, bool $baixado = false): ControleNotaFiscalNota
{
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Grupo Teste',
        'numero_as' => 'AS-'.strtoupper(str()->random(6)),
        'escopo' => 'Escopo de teste',
        'empresa' => 'Empresa Teste',
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 1000,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
    ]);

    $autorizacao = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'status' => AsStatus::ENVIADA,
        'valor' => 1000,
        'valor_estimado' => 1000,
    ]);

    return ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => $autorizacao->id,
        'autorizacao_servico_adicional_id' => null,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'baixado' => $baixado,
    ]);
}

function createNotaFinanceiroNotasFiscaisParaAuxiliar(ControleNotaFiscal $controle, bool $baixado = false): ControleNotaFiscalNota
{
    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-'.strtoupper(str()->random(6)),
        'escopo' => 'Escopo adicional de teste',
        'empresa' => 'Empresa Teste',
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 1000,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
    ]);

    $asa = AsaFactory::new()->create([
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => AsStatus::ENVIADA,
    ]);

    return ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => null,
        'autorizacao_servico_adicional_id' => $asa->id,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'baixado' => $baixado,
    ]);
}

it('ordena unidades por notas aprovadas pendentes usando os vínculos atuais dos documentos', function (): void {
    expect(Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_item_id'))->toBeFalse()
        ->and(Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_auxiliar_id'))->toBeFalse();

    [$obraAlfa, $controleAlfa] = createControleFinanceiroNotasFiscais('Unidade Alfa');
    [, $controleBeta] = createControleFinanceiroNotasFiscais('Unidade Beta');
    [$obraQuitada, $controleQuitada] = createControleFinanceiroNotasFiscais('Unidade Quitada');

    createNotaFinanceiroNotasFiscaisParaItem($controleAlfa, $obraAlfa);
    createNotaFinanceiroNotasFiscaisParaAuxiliar($controleBeta);
    createNotaFinanceiroNotasFiscaisParaAuxiliar($controleBeta);
    createNotaFinanceiroNotasFiscaisParaItem($controleQuitada, $obraQuitada, baixado: true);

    expect(financeiroNotasFiscaisPageProbe()->unidadesOrdenadas())->toBe([
        'Unidade Beta',
        'Unidade Alfa',
        'Unidade Quitada',
    ]);
});

it('carrega a query principal da tabela usando os vínculos atuais dos documentos', function (): void {
    expect(Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_item_id'))->toBeFalse()
        ->and(Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_auxiliar_id'))->toBeFalse();

    [$obraAlfa, $controleAlfa] = createControleFinanceiroNotasFiscais('Unidade Alfa');
    [, $controleBeta] = createControleFinanceiroNotasFiscais('Unidade Beta');

    $notaItem = createNotaFinanceiroNotasFiscaisParaItem($controleAlfa, $obraAlfa);
    $notaAuxiliar = createNotaFinanceiroNotasFiscaisParaAuxiliar($controleBeta);

    $notas = financeiroNotasFiscaisPageProbe()->notasAprovadasDaTabela();

    expect($notas->pluck('id')->all())->toContain($notaItem->id, $notaAuxiliar->id)
        ->and($notas->firstWhere('id', $notaItem->id)?->autorizacaoServico?->controleNotaFiscalItem)->not->toBeNull()
        ->and($notas->firstWhere('id', $notaAuxiliar->id)?->asa?->controleNotaFiscalAuxiliar)->not->toBeNull();
});
