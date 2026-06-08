<?php

namespace App\Services;

use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;
use App\Models\AsEscopo;
use App\Models\Construtora;
use App\Models\ObraRecebimento;
use App\Models\Obras;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ObraRecebimentoSyncService
{
    /**
     * Cria (se necessário) o ObraRecebimento correspondente ao escopo contratado.
     * Idempotente: se já existir item com o mesmo nome na obra, não duplica.
     */
    public function syncCreatedFromEscopo(int $obraId, ?int $asEscopoId, ?string $empresaNome = null): ?ObraRecebimento
    {
        if (! $obraId || ! $asEscopoId) {
            return null;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        $itemNome = trim((string) ($escopo->item_recebimento ?? ''));

        if ($escopo === null || $itemNome === '') {
            return null;
        }

        $construtora = $this->resolverConstrutora($empresaNome);

        $existente = ObraRecebimento::query()
            ->where('obra_id', $obraId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($itemNome)])
            ->first();

        if ($existente instanceof ObraRecebimento) {
            if ($construtora && (int) $existente->construtora_id !== (int) $construtora->id) {
                $existente->update(['construtora_id' => $construtora->id]);
                $this->notificarConstrutora($existente, $construtora, atribuicao: false);
            }

            return $existente;
        }

        $recebimento = ObraRecebimento::create([
            'obra_id' => $obraId,
            'construtora_id' => $construtora?->id,
            'nome' => $itemNome,
            'status' => 'pendente',
            'usuario_id' => Auth::id(),
        ]);

        if ($construtora) {
            $this->notificarConstrutora($recebimento, $construtora, atribuicao: true);
        }

        return $recebimento;
    }

    /**
     * Atualiza o fornecedor do ObraRecebimento associado ao escopo quando a empresa muda no controle de NF.
     * Notifica quando há atribuição/reatribuição.
     */
    public function syncEmpresaAtualizada(int $obraId, ?int $asEscopoId, ?string $empresaNome): void
    {
        if (! $obraId || ! $asEscopoId) {
            return;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        $itemNome = trim((string) ($escopo->item_recebimento ?? ''));

        if ($escopo === null || $itemNome === '') {
            return;
        }

        $recebimento = ObraRecebimento::query()
            ->where('obra_id', $obraId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($itemNome)])
            ->first();

        if (! $recebimento instanceof ObraRecebimento) {
            // Nenhum item criado ainda — cria agora com o fornecedor.
            $this->syncCreatedFromEscopo($obraId, $asEscopoId, $empresaNome);

            return;
        }

        $construtora = $this->resolverConstrutora($empresaNome);
        $construtoraIdAnterior = (int) ($recebimento->construtora_id ?? 0);
        $construtoraIdNova = (int) ($construtora?->id ?? 0);

        if ($construtoraIdAnterior === $construtoraIdNova) {
            return;
        }

        $recebimento->update(['construtora_id' => $construtoraIdNova ?: null]);

        if ($construtora) {
            $this->notificarConstrutora(
                $recebimento,
                $construtora,
                atribuicao: $construtoraIdAnterior === 0
            );
        }
    }

    /**
     * Tenta excluir o ObraRecebimento associado ao escopo.
     * Retorna true se excluiu, false se não pôde por já ter upload (e $forcar = false).
     * Quando $forcar = true, mantém o item intacto (não exclui), conforme regra de negócio.
     */
    public function tentarExcluirPorEscopo(int $obraId, ?int $asEscopoId, bool $forcar = false): bool
    {
        if (! $obraId || ! $asEscopoId) {
            return true;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        $itemNome = trim((string) ($escopo->item_recebimento ?? ''));

        if ($escopo === null || $itemNome === '') {
            return true;
        }

        $recebimento = ObraRecebimento::query()
            ->where('obra_id', $obraId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($itemNome)])
            ->first();

        if (! $recebimento instanceof ObraRecebimento) {
            return true;
        }

        if ($this->temUpload($recebimento)) {
            if ($forcar) {
                // Mantém o item intacto conforme regra B2 (com confirmação prévia do usuário).
                return true;
            }

            return false;
        }

        $recebimento->delete();

        return true;
    }

    /**
     * Verifica se um escopo tem upload no item de recebimento associado.
     */
    public function escopoTemUpload(int $obraId, ?int $asEscopoId): bool
    {
        if (! $obraId || ! $asEscopoId) {
            return false;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        $itemNome = trim((string) ($escopo->item_recebimento ?? ''));

        if ($escopo === null || $itemNome === '') {
            return false;
        }

        $recebimento = ObraRecebimento::query()
            ->where('obra_id', $obraId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($itemNome)])
            ->first();

        return $recebimento instanceof ObraRecebimento && $this->temUpload($recebimento);
    }

    protected function temUpload(ObraRecebimento $r): bool
    {
        return filled($r->foto_entrega_path)
            || (is_array($r->foto_entrega_paths) && array_filter($r->foto_entrega_paths) !== [])
            || filled($r->nota_fiscal_path)
            || (is_array($r->nota_fiscal_paths) && array_filter($r->nota_fiscal_paths) !== []);
    }

    protected function resolverConstrutora(?string $nome): ?Construtora
    {
        $nome = trim((string) $nome);
        if ($nome === '') {
            return null;
        }

        return Construtora::query()->where('nome', $nome)->first();
    }

    protected function notificarConstrutora(ObraRecebimento $recebimento, Construtora $construtora, bool $atribuicao): void
    {
        $usuarios = $construtora->users()->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        $obra = Obras::query()->find($recebimento->obra_id);
        $obraNome = $obra?->projeto?->nome ?? ('Obra #'.$recebimento->obra_id);

        $titulo = $atribuicao
            ? 'Novo item de recebimento atribuído'
            : 'Item de recebimento reatribuído ao seu fornecedor';

        $corpo = sprintf(
            'O item "%s" na obra "%s" está aguardando entrega pela %s.',
            $recebimento->nome,
            $obraNome,
            $construtora->nome
        );

        $urlItem = ObraRecebimentoResource::getUrl('edit', ['record' => $recebimento->id]);

        Notification::make()
            ->title($titulo)
            ->body($corpo)
            ->icon('heroicon-o-truck')
            ->warning()
            ->actions([
                Action::make('ver_item')
                    ->label('Abrir item')
                    ->url($urlItem),
            ])
            ->sendToDatabase($usuarios);
    }
}
