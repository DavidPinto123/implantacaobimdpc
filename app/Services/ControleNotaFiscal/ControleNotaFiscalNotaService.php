<?php

namespace App\Services\ControleNotaFiscal;

use App\Enums\ModoSaldoFiscal;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscalNota;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ControleNotaFiscalNotaService
{
    public function __construct(
        protected ControleNotaFiscalVinculoResolver $resolver,
        protected ControleNotaFiscalAgenteNotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function importarParaAs(array $data, AutorizacaoServico $as, User $user): ControleNotaFiscalNota
    {
        return DB::transaction(function () use ($data, $as, $user): ControleNotaFiscalNota {
            $documento = AutorizacaoServico::query()
                ->whereKey($as->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->load('controleNotaFiscalItem.controleNotaFiscal');

            $item = $documento->controleNotaFiscalItem;
            $controle = $item?->controleNotaFiscal;

            if (! $controle) {
                throw ValidationException::withMessages([
                    'destino' => 'destino_nao_encontrado',
                ]);
            }

            $destino = $this->resolver->resolveAs(
                obraId: (int) $controle->obra_id,
                tipoUnidade: (string) ($controle->tipo_unidade ?: TipoUnidade::EXPANSAO->value),
                autorizacaoServicoId: $documento->id,
                construtoraId: $user->construtoras_id,
                modoSaldo: ModoSaldoFiscal::Comprometido,
            );

            $this->validarDestinoEValor($destino->motivoBloqueio, $destino->saldoDisponivel, $data);

            $nota = ControleNotaFiscalNota::query()->create([
                ...$this->notaPayload($data, $user),
                'autorizacao_servico_id' => $documento->id,
                'autorizacao_servico_adicional_id' => null,
            ]);

            $this->notificationService->notificarNotaImportada($nota->refresh());

            return $nota;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function importarParaAsa(array $data, Asa $asa, User $user): ControleNotaFiscalNota
    {
        return DB::transaction(function () use ($data, $asa, $user): ControleNotaFiscalNota {
            $documento = Asa::query()
                ->whereKey($asa->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->load('controleNotaFiscalAuxiliar.controleNotaFiscal');

            $controle = $documento->controleNotaFiscalAuxiliar?->controleNotaFiscal;

            if (! $controle) {
                throw ValidationException::withMessages([
                    'destino' => 'destino_nao_encontrado',
                ]);
            }

            $destino = $this->resolver->resolveAsa(
                obraId: (int) $controle->obra_id,
                tipoUnidade: (string) ($controle->tipo_unidade ?: TipoUnidade::EXPANSAO->value),
                asaId: $documento->id,
                construtoraId: $user->construtoras_id,
                modoSaldo: ModoSaldoFiscal::Comprometido,
            );

            $this->validarDestinoEValor($destino->motivoBloqueio, $destino->saldoDisponivel, $data);

            $nota = ControleNotaFiscalNota::query()->create([
                ...$this->notaPayload($data, $user),
                'autorizacao_servico_id' => null,
                'autorizacao_servico_adicional_id' => $documento->id,
            ]);

            $this->notificationService->notificarNotaImportada($nota->refresh());

            return $nota;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validarDestinoEValor(?string $motivoBloqueio, float $saldoDisponivel, array $data): void
    {
        if ($motivoBloqueio !== null) {
            throw ValidationException::withMessages([
                'destino' => $motivoBloqueio,
            ]);
        }

        if ((float) ($data['valor_acumulado_medido_nf'] ?? 0) > $saldoDisponivel) {
            throw ValidationException::withMessages([
                'valor_acumulado_medido_nf' => 'saldo_insuficiente',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function notaPayload(array $data, User $user): array
    {
        return [
            'importado_por_id' => $user->id,
            'tipo_medicao' => $data['tipo_medicao'] ?? ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
            'empresa' => $data['empresa'] ?? null,
            'cnpj_fornecedor' => $data['cnpj_fornecedor'] ?? null,
            'numero_nf' => $data['numero_nf'] ?? null,
            'cnpj_faturamento' => $data['cnpj_faturamento'] ?? null,
            'instrucoes_pagamento' => $data['instrucoes_pagamento'] ?? null,
            'boleto_path' => $data['boleto_path'] ?? null,
            'data_vencimento_boleto' => $data['data_vencimento_boleto'] ?? null,
            'banco' => $data['banco'] ?? null,
            'banco_codigo' => $data['banco_codigo'] ?? null,
            'agencia' => $data['agencia'] ?? null,
            'conta_corrente' => $data['conta_corrente'] ?? null,
            'valor_acumulado_medido_nf' => $data['valor_acumulado_medido_nf'] ?? 0,
            'emissao' => $data['emissao'] ?? null,
            'envio' => $data['envio'] ?? null,
            'status' => $data['status'] ?? StatusControleNotaFiscalNota::PENDENTE->value,
            'arquivo_path' => $data['arquivo_path'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
