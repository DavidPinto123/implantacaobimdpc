<?php

namespace App\Services\ControleNotaFiscal;

use App\Enums\AsStatus;
use App\Enums\ModoSaldoFiscal;
use App\Enums\TipoDestinoFiscal;
use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Services\ControleNotaFiscal\Data\DestinoFiscalResolvido;

class ControleNotaFiscalVinculoResolver
{
    public function __construct(
        protected ControleNotaFiscalSaldoService $saldoService,
    ) {}

    public function resolveAs(
        int $obraId,
        string $tipoUnidade,
        ?int $autorizacaoServicoId,
        ?int $construtoraId,
        ModoSaldoFiscal $modoSaldo,
        mixed $itemPrincipal = null,
    ): DestinoFiscalResolvido {
        $controle = $this->controle($obraId, $tipoUnidade);

        if (! $controle instanceof ControleNotaFiscal) {
            return $this->bloqueio(TipoDestinoFiscal::AS, 'controle_nao_encontrado');
        }

        if ($controle->status === ControleNotaFiscal::STATUS_ENCERRADO) {
            return $this->bloqueio(TipoDestinoFiscal::AS, 'controle_encerrado', $controle);
        }

        $as = $autorizacaoServicoId !== null
            ? AutorizacaoServico::query()
                ->with(['controleNotaFiscalItem.controleNotaFiscal'])
                ->whereKey($autorizacaoServicoId)
                ->first()
            : null;

        if (! $as instanceof AutorizacaoServico && $itemPrincipal instanceof ControleNotaFiscalItem) {
            $as = AutorizacaoServico::query()
                ->with(['controleNotaFiscalItem.controleNotaFiscal'])
                ->where('controle_nota_fiscal_item_id', $itemPrincipal->id)
                ->latest('id')
                ->first();
        }

        $item = $itemPrincipal ?? $as?->controleNotaFiscalItem;

        if ($controle->status === ControleNotaFiscal::STATUS_ENCERRADO) {
            return $this->bloqueio(TipoDestinoFiscal::AS, 'controle_encerrado', $controle);
        }

        if (! $item || $item->controle_nota_fiscal_id !== $controle->id || ($autorizacaoServicoId !== null && ! $as instanceof AutorizacaoServico)) {
            return $this->bloqueio(TipoDestinoFiscal::AS, 'destino_nao_encontrado', $controle);
        }

        if (
            $construtoraId !== null
            && (
                $as instanceof AutorizacaoServico
                    ? (int) $as->construtora_id !== $construtoraId
                    : (filled($item->empresa) && ! $this->fornecedorAuxiliarConfere($item->empresa, $construtoraId))
            )
        ) {
            return $this->bloqueio(TipoDestinoFiscal::AS, 'fornecedor_divergente', $controle, documentoAs: $as, itemPrincipal: $item);
        }

        if (
            $modoSaldo === ModoSaldoFiscal::Comprometido
            && (! $as instanceof AutorizacaoServico || $as->status !== AsStatus::ENVIADA || $item->liberado_para_fornecedor_at === null)
        ) {
            return $this->bloqueio(TipoDestinoFiscal::AS, 'item_nao_liberado', $controle, documentoAs: $as, itemPrincipal: $item);
        }

        return new DestinoFiscalResolvido(
            controle: $controle,
            tipo: TipoDestinoFiscal::AS,
            documentoAs: $as,
            itemPrincipal: $item,
            saldoDisponivel: $as instanceof AutorizacaoServico
                ? $this->saldoService->saldoParaAs($as, $modoSaldo)
                : (float) ($item->saldo ?? $item->valor_global_a ?? 0),
        );
    }

    public function resolveAsa(
        int $obraId,
        string $tipoUnidade,
        int $asaId,
        ?int $construtoraId,
        ModoSaldoFiscal $modoSaldo,
    ): DestinoFiscalResolvido {
        $controle = $this->controle($obraId, $tipoUnidade);

        if (! $controle instanceof ControleNotaFiscal) {
            return $this->bloqueio(TipoDestinoFiscal::ASA, 'controle_nao_encontrado');
        }

        if ($controle->status === ControleNotaFiscal::STATUS_ENCERRADO) {
            return $this->bloqueio(TipoDestinoFiscal::ASA, 'controle_encerrado', $controle);
        }

        $asa = Asa::query()
            ->with(['controleNotaFiscalAuxiliar.controleNotaFiscal'])
            ->whereKey($asaId)
            ->first();

        $auxiliar = $asa?->controleNotaFiscalAuxiliar;

        if ($controle->status === ControleNotaFiscal::STATUS_ENCERRADO) {
            return $this->bloqueio(TipoDestinoFiscal::ASA, 'controle_encerrado', $controle);
        }

        if (! $asa instanceof Asa || ! $auxiliar || $auxiliar->controle_nota_fiscal_id !== $controle->id) {
            return $this->bloqueio(TipoDestinoFiscal::ASA, 'destino_nao_encontrado', $controle);
        }

        if ($construtoraId !== null && ! $this->fornecedorAuxiliarConfere($auxiliar->empresa, $construtoraId)) {
            return $this->bloqueio(TipoDestinoFiscal::ASA, 'fornecedor_divergente', $controle, documentoAsa: $asa, itemAuxiliar: $auxiliar);
        }

        if (! in_array($asa->status, [AsStatus::APROVADO, AsStatus::CRIADA, AsStatus::ENVIADA], true)) {
            return $this->bloqueio(TipoDestinoFiscal::ASA, 'asa_nao_aprovada', $controle, documentoAsa: $asa, itemAuxiliar: $auxiliar);
        }

        if ($modoSaldo === ModoSaldoFiscal::Comprometido && $auxiliar->liberado_para_fornecedor_at === null) {
            return $this->bloqueio(TipoDestinoFiscal::ASA, 'item_nao_liberado', $controle, documentoAsa: $asa, itemAuxiliar: $auxiliar);
        }

        return new DestinoFiscalResolvido(
            controle: $controle,
            tipo: TipoDestinoFiscal::ASA,
            documentoAsa: $asa,
            itemAuxiliar: $auxiliar,
            saldoDisponivel: $this->saldoService->saldoParaAsa($asa, $modoSaldo),
        );
    }

    protected function controle(int $obraId, string $tipoUnidade): ?ControleNotaFiscal
    {
        return ControleNotaFiscal::query()
            ->where('obra_id', $obraId)
            ->where('tipo_unidade', $tipoUnidade)
            ->first();
    }

    protected function fornecedorAuxiliarConfere(?string $empresa, int $construtoraId): bool
    {
        $construtora = Construtora::query()->find($construtoraId);

        return $construtora instanceof Construtora
            && trim((string) $empresa) !== ''
            && mb_strtolower(trim((string) $empresa)) === mb_strtolower(trim($construtora->nome));
    }

    protected function bloqueio(
        TipoDestinoFiscal $tipo,
        string $motivo,
        ?ControleNotaFiscal $controle = null,
        ?AutorizacaoServico $documentoAs = null,
        ?Asa $documentoAsa = null,
        mixed $itemPrincipal = null,
        mixed $itemAuxiliar = null,
    ): DestinoFiscalResolvido {
        return new DestinoFiscalResolvido(
            controle: $controle,
            tipo: $tipo,
            documentoAs: $documentoAs,
            documentoAsa: $documentoAsa,
            itemPrincipal: $itemPrincipal,
            itemAuxiliar: $itemAuxiliar,
            motivoBloqueio: $motivo,
        );
    }
}
