<?php

namespace App\Filament\Pages;

use App\Enums\MotivoAlteracaoObra;
use App\Models\CronogramaFaseHistorico;
use App\Models\Projeto;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AprovacaoMudancaPosse extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-badge';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'PMO';

    protected static ?string $navigationLabel = 'Aprovar mudança de Posse';

    protected static ?string $title = 'Aprovação de mudança da Data de Posse';

    protected static ?string $slug = 'aprovacao-mudanca-posse';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.aprovacao-mudanca-posse';

    public static function canAccess(): bool
    {
        return Auth::user()?->can('View:AprovacaoMudancaPosse') ?? false;
    }

    public function aprovar(int $projetoId): void
    {
        if (! self::canAccess()) {
            return;
        }

        $projeto = Projeto::find($projetoId);
        if (! $projeto || ! $projeto->data_posse_pendente) {
            return;
        }

        $valorAnterior = $projeto->data_posse?->toDateString();
        $valorNovo = $projeto->data_posse_pendente->toDateString();

        // Aplica a data + grava histórico de aprovação
        $projeto->motivo_alteracao_posse_codigo = $projeto->data_posse_pendente_motivo_codigo;
        $projeto->motivo_alteracao_posse_historico = sprintf(
            'Aprovação de mudança Data da Posse (solicitação de %s): %s',
            $projeto->dataPossePendenteSolicitante?->name ?? 'usuário',
            $projeto->data_posse_pendente_motivo ?? ''
        );
        $projeto->data_posse = $valorNovo;
        $projeto->data_posse_pendente = null;
        $projeto->data_posse_pendente_motivo = null;
        $projeto->data_posse_pendente_motivo_codigo = null;
        $projeto->data_posse_pendente_user_id = null;
        $projeto->data_posse_pendente_solicitada_em = null;
        $projeto->save();

        Notification::make()
            ->title('Mudança de Posse aprovada')
            ->body($projeto->nome.' — '.($valorAnterior ?? '—').' → '.$valorNovo)
            ->success()
            ->send();
    }

    public function rejeitar(int $projetoId, ?string $motivoRejeicao = null): void
    {
        if (! self::canAccess()) {
            return;
        }

        $projeto = Projeto::find($projetoId);
        if (! $projeto || ! $projeto->data_posse_pendente) {
            return;
        }

        CronogramaFaseHistorico::create([
            'projeto_id' => $projeto->id,
            'cronograma_fase_id' => null,
            'campo_alterado' => 'projeto.data_posse',
            'valor_anterior' => $projeto->data_posse?->toDateString(),
            'valor_novo' => $projeto->data_posse_pendente->toDateString(),
            'motivo' => 'Rejeição de mudança Data da Posse — '.($motivoRejeicao ?? 'sem justificativa'),
            'motivo_codigo' => $projeto->data_posse_pendente_motivo_codigo,
            'usuario_id' => Auth::id(),
            'automatico' => false,
        ]);

        $projeto->data_posse_pendente = null;
        $projeto->data_posse_pendente_motivo = null;
        $projeto->data_posse_pendente_motivo_codigo = null;
        $projeto->data_posse_pendente_user_id = null;
        $projeto->data_posse_pendente_solicitada_em = null;
        $projeto->save();

        Notification::make()
            ->title('Mudança de Posse rejeitada')
            ->warning()
            ->send();
    }

    public function getViewData(): array
    {
        $projetos = Projeto::query()
            ->whereNotNull('data_posse_pendente')
            ->with('dataPossePendenteSolicitante:id,name')
            ->orderBy('data_posse_pendente_solicitada_em', 'desc')
            ->get();

        return [
            'projetos' => $projetos,
            'motivos' => MotivoAlteracaoObra::cases(),
        ];
    }
}
