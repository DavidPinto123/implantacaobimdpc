<?php

namespace App\Livewire\Obras;

use App\Enums\CategoriaAtualizacaoObra;
use App\Models\AtualizacaoObra;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Feed compartilhado de atualizações/histórico de Obras.
 *
 * Dois modos de operação:
 *  - obraId: feed de uma obra específica (com postar/responder comentários).
 *  - obraIds: feed agregado para um conjunto de obras (somente leitura).
 */
class HistoricoObra extends Component
{
    use WithPagination;

    public ?int $obraId = null;

    /** @var array<int, int>|null */
    public ?array $obraIds = null;

    public string $buscaTexto = '';

    public ?string $categoriaFiltro = null;

    public string $novoComentario = '';

    public ?int $respondendoA = null;

    public string $respostaTexto = '';

    public string $buscaMencao = '';

    /** @var array<int, array{id:int,name:string}> */
    public array $sugestoesMencao = [];

    public function mount(?int $obraId = null, ?array $obraIds = null): void
    {
        $this->obraId = $obraId;
        $this->obraIds = $obraIds !== null
            ? array_values(array_unique(array_map('intval', $obraIds)))
            : null;
    }

    public function isGlobal(): bool
    {
        return $this->obraId === null;
    }

    public function updatedBuscaTexto(): void
    {
        $this->resetPage();
    }

    public function updatedCategoriaFiltro(): void
    {
        $this->resetPage();
    }

    public function postarComentario(): void
    {
        if ($this->isGlobal() || trim($this->novoComentario) === '') {
            return;
        }

        $mencoes = $this->extrairMencoes($this->novoComentario);

        AtualizacaoObra::create([
            'obra_id' => $this->obraId,
            'usuario_id' => auth()->id(),
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Comentário',
            'conteudo' => $this->novoComentario,
            'mencoes' => ! empty($mencoes) ? $mencoes : null,
            'automatico' => false,
        ]);

        $this->reset('novoComentario');

        Notification::make()->title('Comentário publicado')->success()->send();
    }

    public function abrirResposta(int $parentId): void
    {
        if ($this->isGlobal()) {
            return;
        }

        $this->respondendoA = $parentId;
        $this->respostaTexto = '';
    }

    public function fecharResposta(): void
    {
        $this->reset(['respondendoA', 'respostaTexto']);
    }

    public function responder(): void
    {
        if ($this->isGlobal() || ! $this->respondendoA || trim($this->respostaTexto) === '') {
            return;
        }

        $parent = AtualizacaoObra::find($this->respondendoA);

        if (! $parent || $parent->obra_id !== $this->obraId) {
            return;
        }

        $mencoes = $this->extrairMencoes($this->respostaTexto);

        AtualizacaoObra::create([
            'obra_id' => $this->obraId,
            'usuario_id' => auth()->id(),
            'parent_id' => $this->respondendoA,
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Resposta',
            'conteudo' => $this->respostaTexto,
            'mencoes' => ! empty($mencoes) ? $mencoes : null,
            'automatico' => false,
        ]);

        $this->reset(['respondendoA', 'respostaTexto']);

        Notification::make()->title('Resposta publicada')->success()->send();
    }

    public function excluirAtualizacao(int $id): void
    {
        $atualizacao = AtualizacaoObra::find($id);

        if (! $atualizacao) {
            return;
        }

        if ($atualizacao->usuario_id !== auth()->id() && ! auth()->user()?->can('Delete:AtualizacaoObra')) {
            Notification::make()->title('Sem permissão')->danger()->send();

            return;
        }

        $atualizacao->delete();

        Notification::make()->title('Removido')->success()->send();
    }

    public function fixarAtualizacao(int $id): void
    {
        $atualizacao = AtualizacaoObra::find($id);

        if (! $atualizacao) {
            return;
        }

        $atualizacao->update(['fixado' => ! $atualizacao->fixado]);

        Notification::make()
            ->title($atualizacao->fixado ? 'Fixado' : 'Desfixado')
            ->success()
            ->send();
    }

    public function buscarUsuarios(string $query): void
    {
        $this->buscaMencao = $query;

        if (strlen($query) < 2) {
            $this->sugestoesMencao = [];

            return;
        }

        $this->sugestoesMencao = User::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->toArray();
    }

    #[Computed]
    public function atualizacoes()
    {
        $query = AtualizacaoObra::query()
            ->with(['usuario', 'respostas.usuario', 'obra.projeto'])
            ->whereNull('parent_id');

        if ($this->obraId !== null) {
            $query->where('obra_id', $this->obraId);
        } elseif ($this->obraIds !== null) {
            if ($this->obraIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('obra_id', $this->obraIds);
            }
        }

        if ($this->categoriaFiltro !== null && $this->categoriaFiltro !== '') {
            $query->where('categoria', $this->categoriaFiltro);
        }

        $termo = trim($this->buscaTexto);
        if ($termo !== '') {
            $query->where(function ($q) use ($termo): void {
                $q->where('titulo', 'like', "%{$termo}%")
                    ->orWhere('conteudo', 'like', "%{$termo}%")
                    ->orWhere('valor_anterior', 'like', "%{$termo}%")
                    ->orWhere('valor_novo', 'like', "%{$termo}%");
            });
        }

        return $query
            ->orderByDesc('fixado')
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    /**
     * @return array<string, array{label:string, color:string}>
     */
    public function getCategoriasOptions(): array
    {
        $options = [];

        foreach (CategoriaAtualizacaoObra::cases() as $case) {
            $options[$case->value] = [
                'label' => $case->label(),
                'color' => $case->color(),
            ];
        }

        return $options;
    }

    public function render(): View
    {
        return view('livewire.obras.historico-obra', [
            'atualizacoes' => $this->atualizacoes,
            'categoriasOptions' => $this->getCategoriasOptions(),
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function extrairMencoes(string $texto): array
    {
        preg_match_all('/@(\w[\w\s]*?)(?=\s@|\s*$|[,;.!?])/', $texto, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $nomes = array_map('trim', $matches[1]);

        return User::whereIn('name', $nomes)
            ->pluck('id')
            ->toArray();
    }
}
