<?php

namespace App\Filament\Pages\PosObra;

use App\Enums\PosObra\StatusPendencia;
use App\Filament\Resources\PosObra\PendenciaResource;
use App\Models\PosObra\Pendencia;
use App\Services\PosObra\PendenciaService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class AprovacoesPage extends Page
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-circle';

    protected static UnitEnum|string|null $navigationGroup = 'Pós Obra';

    protected static ?string $navigationLabel = 'Aprovações';

    protected static ?string $title = 'Aprovações de Conclusão';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.pos-obra.aprovacoes-pendencias';

    public array $pendencias = [];

    public int $total = 0;

    public ?int $rejeitandoId = null;

    public string $motivoRejeicao = '';

    public string $previewUrl = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $items = Pendencia::query()
            ->where('status', StatusPendencia::AGUARDANDO_APROVACAO)
            ->with([
                'obra:id,projeto_id,unidade,endereco',
                'obra.projeto:id,sigla',
                'construtora:id,nome',
                'gestor:id,name',
                'liderObra:id,name',
                'disciplina:id,label',
                'anexos',
            ])
            ->orderBy('updated_at', 'asc')
            ->get();

        $this->total = $items->count();

        $this->pendencias = $items->map(fn ($p) => [
            'id' => $p->id,
            'codigo' => $p->codigo,
            'descricao' => $p->descricao,
            'local_especifico' => $p->local_especifico,
            'urgencia' => $p->urgencia?->value,
            'urgencia_label' => $p->urgencia?->label(),
            'disciplina' => $p->disciplina?->label,
            'obra' => $p->obra?->sigla ?? $p->obra?->unidade,
            'obra_sub' => ($p->obra?->sigla && $p->obra?->unidade) ? $p->obra->unidade : null,
            'construtora' => $p->construtora?->nome,
            'gestor' => $p->gestor?->name,
            'lider_obra' => $p->liderObra?->name,
            'updated_at' => $p->updated_at->format('d/m/Y H:i'),
            'url' => PendenciaResource::getUrl('view', ['record' => $p->id]),
            'anexos' => $p->anexos->map(fn ($a) => [
                'id' => $a->id,
                'url' => $a->url,
                'nome_arquivo' => $a->nome_arquivo,
            ])->toArray(),
        ])->toArray();
    }

    public function aprovar(int $id): void
    {
        $pendencia = Pendencia::findOrFail($id);

        app(PendenciaService::class)->registrarAtualizacaoStatus(
            $pendencia,
            StatusPendencia::CONCLUIDA,
            auth()->user()->name ?? 'Painel',
            'Conclusão aprovada pelo gestor via painel',
        );

        $this->loadData();

        Notification::make()->title('Pendência aprovada como Concluída')->success()->send();
    }

    public function iniciarRejeicao(int $id): void
    {
        $this->rejeitandoId = $id;
        $this->motivoRejeicao = '';
    }

    public function confirmarRejeicao(): void
    {
        if (! $this->rejeitandoId || ! trim($this->motivoRejeicao)) {
            return;
        }

        $pendencia = Pendencia::findOrFail($this->rejeitandoId);

        app(PendenciaService::class)->registrarAtualizacaoStatus(
            $pendencia,
            StatusPendencia::EM_EXECUCAO,
            auth()->user()->name ?? 'Painel',
            'Conclusão rejeitada: '.trim($this->motivoRejeicao),
        );

        $this->reset(['rejeitandoId', 'motivoRejeicao']);
        $this->loadData();

        Notification::make()->title('Conclusão rejeitada — pendência retornou a Em Execução')->warning()->send();
    }

    public function cancelarRejeicao(): void
    {
        $this->reset(['rejeitandoId', 'motivoRejeicao']);
    }

    public function abrirPreview(string $url): void
    {
        $this->previewUrl = $url;
    }

    public function fecharPreview(): void
    {
        $this->previewUrl = '';
    }
}
