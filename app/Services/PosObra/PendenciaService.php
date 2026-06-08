<?php

namespace App\Services\PosObra;

use App\Enums\PosObra\StatusPendencia;
use App\Models\PosObra\AtualizacaoStatus;
use App\Models\PosObra\Pendencia;
use Illuminate\Support\Carbon;

class PendenciaService
{
    public function gerarCodigo(): string
    {
        $ano = Carbon::now()->year;
        $ultimo = Pendencia::whereYear('created_at', $ano)
            ->orderByDesc('id')
            ->value('codigo');

        $sequencia = 1;
        if ($ultimo) {
            // formato: PO-YYYY-XXXX
            $sequencia = (int) substr($ultimo, -4) + 1;
        }

        return sprintf('PO-%d-%04d', $ano, $sequencia);
    }

    public function registrarAtualizacaoStatus(
        Pendencia $pendencia,
        StatusPendencia $novoStatus,
        string $atualizadoPor,
        ?string $comentario = null
    ): AtualizacaoStatus {
        $anterior = $pendencia->status;

        $pendencia->status = $novoStatus;

        if ($novoStatus === StatusPendencia::CONCLUIDA) {
            $pendencia->data_conclusao = now();
        }

        $pendencia->save();

        return AtualizacaoStatus::create([
            'pendencia_id' => $pendencia->id,
            'status_anterior' => $anterior->value,
            'status_novo' => $novoStatus->value,
            'comentario' => $comentario,
            'atualizado_por' => $atualizadoPor,
        ]);
    }

    public function avancarStatus(Pendencia $pendencia, string $atualizadoPor, ?string $comentario = null): void
    {
        $proximo = match ($pendencia->status) {
            StatusPendencia::REGISTRADA => StatusPendencia::NOTIFICADA_PRESTADORA,
            StatusPendencia::NOTIFICADA_PRESTADORA => StatusPendencia::PENDENTE_COM_PRAZO,
            StatusPendencia::PENDENTE_COM_PRAZO => StatusPendencia::EM_EXECUCAO,
            StatusPendencia::EM_EXECUCAO => StatusPendencia::AGUARDANDO_APROVACAO,
            default => null,
        };

        if ($proximo) {
            $this->registrarAtualizacaoStatus($pendencia, $proximo, $atualizadoPor, $comentario);
        }
    }
}
