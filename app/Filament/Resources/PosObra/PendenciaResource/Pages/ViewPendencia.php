<?php

namespace App\Filament\Resources\PosObra\PendenciaResource\Pages;

use App\Enums\PosObra\StatusPendencia;
use App\Filament\Resources\PosObra\PendenciaResource;
use App\Services\PosObra\PendenciaService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPendencia extends ViewRecord
{
    protected static string $resource = PendenciaResource::class;

    protected string $view = 'filament.pages.pos-obra.view-pendencia';

    public bool $showStatusModal = false;

    public string $novoStatus = '';

    public string $comentario = '';

    private const TRANSICOES = [
        'REGISTRADA' => ['NOTIFICADA_PRESTADORA', 'PENDENTE_COM_PRAZO', 'EM_EXECUCAO', 'CANCELADA'],
        'NOTIFICADA_PRESTADORA' => ['PENDENTE_COM_PRAZO', 'EM_EXECUCAO', 'CANCELADA'],
        'PENDENTE_COM_PRAZO' => ['EM_EXECUCAO', 'AGUARDANDO_APROVACAO', 'CANCELADA'],
        'EM_EXECUCAO' => ['AGUARDANDO_APROVACAO', 'AS_ORCAMENTOS', 'GARANTIA_SOLICITADA', 'PROJ_COMPLEMENTAR', 'CANCELADA'],
        'AGUARDANDO_APROVACAO' => ['CONCLUIDA', 'EM_EXECUCAO', 'CANCELADA'],
        'CONCLUIDA' => [],
        'AS_ORCAMENTOS' => [],
        'GARANTIA_SOLICITADA' => [],
        'PROJ_COMPLEMENTAR' => [],
        'CANCELADA' => ['REGISTRADA'],
    ];

    public function getTransicoesDisponiveis(): array
    {
        $key = $this->record->status instanceof StatusPendencia
            ? $this->record->status->value
            : (string) $this->record->status;

        return self::TRANSICOES[$key] ?? [];
    }

    public function openStatusModal(): void
    {
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->reset(['showStatusModal', 'novoStatus', 'comentario']);
    }

    public function confirmStatusUpdate(): void
    {
        if (! $this->novoStatus) {
            return;
        }

        $status = StatusPendencia::from($this->novoStatus);

        app(PendenciaService::class)->registrarAtualizacaoStatus(
            $this->record,
            $status,
            auth()->user()->name ?? 'Painel',
            $this->comentario ?: null,
        );

        $this->record->refresh();
        $this->reset(['showStatusModal', 'novoStatus', 'comentario']);

        Notification::make()->title('Status atualizado')->success()->send();
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
