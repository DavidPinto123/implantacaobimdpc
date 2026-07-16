<?php

namespace App\Filament\Pages;

use App\Models\Orcamento;
use App\Models\OrcamentoCategoria;
use App\Models\OrcamentoRevitItem;
use App\Models\Projeto;
use App\Traits\HasMenuPermission;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OrcamentosPage extends Page
{
    use HasMenuPermission;

    protected string $view = 'filament.pages.orcamentos-page';

    protected static ?string $navigationLabel = 'Orçamento de Obras';

    protected static \UnitEnum|string|null $navigationGroup = 'Orçamentos';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Orçamento de Obras';

    protected static function menuPermission(): string
    {
        return 'View:MenuOrcamentoObras';
    }

    // ─── Drill-down ──────────────────────────────────────────────────────────
    public ?int $projetoId = null;
    public ?string $projetoNome = null;

    // ─── Modais ──────────────────────────────────────────────────────────────
    public bool $modalFormAberto = false;
    public bool $modalDetalheAberto = false;

    // ─── IDs ativos ──────────────────────────────────────────────────────────
    public ?int $editandoId = null;
    public ?int $detalheId = null;

    // ─── Campos do formulário ────────────────────────────────────────────────
    public ?int $formProjetoId = null;
    public string $formNome = '';
    public string $formNomeMkt = '';
    public string $formData = '';
    public array $formCategorias = [];

    // ─── Navegação ───────────────────────────────────────────────────────────

    public function selecionarProjeto(int $id, string $nome): void
    {
        $this->projetoId   = $id;
        $this->projetoNome = $nome;
        $this->fecharTudo();
    }

    public function voltar(): void
    {
        $this->projetoId   = null;
        $this->projetoNome = null;
        $this->fecharTudo();
    }

    // ─── Dados ───────────────────────────────────────────────────────────────

    public function getProjetos(): Collection
    {
        return Projeto::query()
            ->withCount('orcamentos')
            ->withMax('orcamentos', 'data')
            ->orderBy('nome')
            ->get();
    }

    public function getOrcamentosDoProjeto(): Collection
    {
        if (! $this->projetoId) {
            return collect();
        }

        return Orcamento::where('projeto_id', $this->projetoId)
            ->withCount('categorias')
            ->orderByDesc('data')
            ->get();
    }

    public function getOrcamentoDetalhe(): ?Orcamento
    {
        if (! $this->detalheId) {
            return null;
        }

        return Orcamento::with(['projeto', 'categorias.itens', 'criador'])->find($this->detalheId);
    }

    public function getCategoriasSugeridas(): array
    {
        return OrcamentoCategoria::query()
            ->distinct()
            ->orderBy('nome')
            ->pluck('nome')
            ->toArray();
    }

    // ─── Modal: Detalhe ──────────────────────────────────────────────────────

    public function abrirDetalhe(int $id): void
    {
        $this->detalheId          = $id;
        $this->modalDetalheAberto = true;
    }

    public function fecharDetalhe(): void
    {
        $this->detalheId          = null;
        $this->modalDetalheAberto = false;
    }

    // ─── Modal: Formulário ───────────────────────────────────────────────────

    public function novoOrcamento(): void
    {
        $this->editandoId     = null;
        $this->formProjetoId  = $this->projetoId;
        $this->formNome       = '';
        $this->formNomeMkt    = '';
        $this->formData       = now()->format('Y-m-d');
        $this->formCategorias = [];
        $this->resetValidation();
        $this->modalFormAberto = true;
    }

    public function editarOrcamento(int $id): void
    {
        $orcamento = Orcamento::with('categorias.itens')->findOrFail($id);

        $this->editandoId    = $id;
        $this->formProjetoId = $orcamento->projeto_id;
        $this->formNome      = $orcamento->nome;
        $this->formNomeMkt   = $orcamento->nome_mkt ?? '';
        $this->formData      = $orcamento->data?->format('Y-m-d') ?? '';

        $this->formCategorias = $orcamento->categorias
            ->map(fn (OrcamentoCategoria $categoria) => [
                'id'    => $categoria->id,
                'nome'  => $categoria->nome,
                'itens' => $categoria->itens
                    ->map(fn ($item) => [
                        'id'         => $item->id,
                        'codigo'     => $item->codigo ?? '',
                        'descricao'  => $item->descricao,
                        'unidade'    => $item->unidade,
                        'quantidade' => (string) $item->quantidade,
                        'valor_mat'  => (string) $item->valor_mat,
                        'valor_mo'   => (string) $item->valor_mo,
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->values()
            ->toArray();

        $this->resetValidation();
        $this->modalFormAberto   = true;
        $this->modalDetalheAberto = false;
    }

    public function fecharModal(): void
    {
        $this->modalFormAberto = false;
        $this->editandoId      = null;
    }

    // ─── Categorias ──────────────────────────────────────────────────────────

    public function adicionarCategoria(): void
    {
        $this->formCategorias[] = ['id' => null, 'nome' => '', 'itens' => []];
    }

    public function removerCategoria(int $index): void
    {
        array_splice($this->formCategorias, $index, 1);
        $this->formCategorias = array_values($this->formCategorias);
    }

    // ─── Itens ───────────────────────────────────────────────────────────────

    public function adicionarItem(int $catIndex): void
    {
        $this->formCategorias[$catIndex]['itens'][] = [
            'id'         => null,
            'codigo'     => '',
            'descricao'  => '',
            'unidade'    => 'un',
            'quantidade' => '1',
            'valor_mat'  => '0',
            'valor_mo'   => '0',
        ];
    }

    public function removerItem(int $catIndex, int $itemIndex): void
    {
        array_splice($this->formCategorias[$catIndex]['itens'], $itemIndex, 1);
        $this->formCategorias[$catIndex]['itens'] = array_values($this->formCategorias[$catIndex]['itens']);
    }

    // ─── Sincronização com o Revit ──────────────────────────────────────────

    public function sincronizarRevit(): void
    {
        if (! $this->formProjetoId) {
            Notification::make()->title('Selecione um projeto antes de sincronizar.')->warning()->send();

            return;
        }

        $projeto = Projeto::find($this->formProjetoId);
        $codigoObra = $projeto?->nova_sigla;

        if (! $codigoObra) {
            Notification::make()
                ->title('Este projeto não tem "Nova Sigla" cadastrada')
                ->body('Não é possível localizar itens do Revit sem esse código.')
                ->warning()
                ->send();

            return;
        }

        $itensPorCategoria = OrcamentoRevitItem::where('codigo_obra', $codigoObra)
            ->orderBy('categoria')
            ->orderBy('ordem')
            ->get()
            ->groupBy('categoria');

        if ($itensPorCategoria->isEmpty()) {
            Notification::make()
                ->title("Nenhum item do Revit encontrado para \"{$codigoObra}\"")
                ->warning()
                ->send();

            return;
        }

        $adicionados = 0;
        $atualizados = 0;

        foreach ($itensPorCategoria as $nomeCategoria => $itensRevit) {
            $catIndex = collect($this->formCategorias)->search(
                fn ($cat) => mb_strtolower(trim($cat['nome'])) === mb_strtolower(trim($nomeCategoria))
            );

            if ($catIndex === false) {
                $catIndex = count($this->formCategorias);
                $this->formCategorias[] = ['id' => null, 'nome' => $nomeCategoria, 'itens' => []];
            }

            foreach ($itensRevit as $itemRevit) {
                $itemIndex = collect($this->formCategorias[$catIndex]['itens'])->search(
                    fn ($item) => filled($item['codigo']) && $item['codigo'] === $itemRevit->codigo
                );

                $dadosItem = [
                    'codigo' => $itemRevit->codigo,
                    'descricao' => $itemRevit->descricao,
                    'unidade' => $itemRevit->unidade ?: 'un',
                    'quantidade' => (string) $itemRevit->quantidade,
                    'valor_mat' => (string) $itemRevit->valor_mat,
                    'valor_mo' => (string) $itemRevit->valor_mo,
                ];

                if ($itemIndex !== false) {
                    $idExistente = $this->formCategorias[$catIndex]['itens'][$itemIndex]['id'] ?? null;
                    $this->formCategorias[$catIndex]['itens'][$itemIndex] = ['id' => $idExistente, ...$dadosItem];
                    $atualizados++;
                } else {
                    $this->formCategorias[$catIndex]['itens'][] = ['id' => null, ...$dadosItem];
                    $adicionados++;
                }
            }
        }

        Notification::make()
            ->title('Sincronizado com o Revit')
            ->body("{$adicionados} itens novos, {$atualizados} atualizados. Revise e clique em Salvar para confirmar.")
            ->success()
            ->send();
    }

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function salvar(): void
    {
        $this->validate([
            'formProjetoId'                        => 'required|exists:projetos,id',
            'formNome'                              => 'required|string|max:255',
            'formNomeMkt'                           => 'nullable|string|max:255',
            'formData'                              => 'required|date',
            'formCategorias.*.nome'                 => 'required|string|max:255',
            'formCategorias.*.itens.*.descricao'    => 'required|string',
            'formCategorias.*.itens.*.unidade'      => 'required|string|max:20',
            'formCategorias.*.itens.*.quantidade'   => 'required|numeric|min:0',
            'formCategorias.*.itens.*.valor_mat'    => 'nullable|numeric|min:0',
            'formCategorias.*.itens.*.valor_mo'     => 'nullable|numeric|min:0',
        ], [
            'formProjetoId.required'                     => 'Selecione um projeto.',
            'formNome.required'                          => 'Informe o nome do orçamento.',
            'formData.required'                          => 'Informe a data do orçamento.',
            'formCategorias.*.nome.required'              => 'Informe o nome da categoria.',
            'formCategorias.*.itens.*.descricao.required' => 'Informe a descrição do item.',
            'formCategorias.*.itens.*.unidade.required'   => 'Informe a unidade do item.',
            'formCategorias.*.itens.*.quantidade.required' => 'Informe a quantidade do item.',
        ]);

        $dados = [
            'projeto_id' => $this->formProjetoId,
            'nome'       => $this->formNome,
            'nome_mkt'   => $this->formNomeMkt ?: null,
            'data'       => $this->formData,
        ];

        if ($this->editandoId) {
            $orcamento = Orcamento::findOrFail($this->editandoId);
            $orcamento->update($dados);
        } else {
            $dados['criado_por'] = auth()->id();
            $orcamento = Orcamento::create($dados);
        }

        // Categorias — update-in-place
        $catIdsNoForm = collect($this->formCategorias)->pluck('id')->filter()->map(fn ($id) => (int) $id)->toArray();
        $orcamento->categorias()->whereNotIn('id', $catIdsNoForm)->delete();

        foreach ($this->formCategorias as $catIndex => $cat) {
            if (! trim($cat['nome'] ?? '')) {
                continue;
            }

            $catId = isset($cat['id']) && $cat['id'] ? (int) $cat['id'] : null;

            if ($catId) {
                $categoria = $orcamento->categorias()->find($catId);
                if ($categoria) {
                    $categoria->update(['nome' => $cat['nome'], 'ordem' => $catIndex]);
                }
            } else {
                $categoria = $orcamento->categorias()->create(['nome' => $cat['nome'], 'ordem' => $catIndex]);
            }

            if (! $categoria) {
                continue;
            }

            // Itens — update-in-place
            $itemIdsNoForm = collect($cat['itens'] ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id)->toArray();
            $categoria->itens()->whereNotIn('id', $itemIdsNoForm)->delete();

            foreach (($cat['itens'] ?? []) as $itemIndex => $item) {
                if (! trim($item['descricao'] ?? '')) {
                    continue;
                }

                $itemId = isset($item['id']) && $item['id'] ? (int) $item['id'] : null;

                $dadosItem = [
                    'codigo'     => $item['codigo'] ?: null,
                    'descricao'  => $item['descricao'],
                    'unidade'    => $item['unidade'] ?: 'un',
                    'quantidade' => (float) ($item['quantidade'] ?? 0),
                    'valor_mat'  => (float) ($item['valor_mat'] ?? 0),
                    'valor_mo'   => (float) ($item['valor_mo'] ?? 0),
                    'ordem'      => $itemIndex,
                ];

                if ($itemId) {
                    $it = $categoria->itens()->find($itemId);
                    if ($it) {
                        $it->update($dadosItem);
                    }
                } else {
                    $categoria->itens()->create($dadosItem);
                }
            }
        }

        $this->fecharModal();

        Notification::make()
            ->title($this->editandoId ? 'Orçamento atualizado com sucesso' : 'Orçamento criado com sucesso')
            ->success()
            ->send();
    }

    public function deletarOrcamento(int $id): void
    {
        Orcamento::findOrFail($id)->delete();

        Notification::make()->title('Orçamento removido')->success()->send();
    }

    public function duplicarOrcamento(int $id): void
    {
        $original = Orcamento::with('categorias.itens')->findOrFail($id);

        $novo = Orcamento::create([
            'projeto_id' => $original->projeto_id,
            'nome'       => $original->nome . ' (cópia)',
            'nome_mkt'   => $original->nome_mkt,
            'data'       => now()->format('Y-m-d'),
            'criado_por' => auth()->id(),
        ]);

        foreach ($original->categorias as $categoria) {
            $novaCategoria = $novo->categorias()->create([
                'nome'  => $categoria->nome,
                'ordem' => $categoria->ordem,
            ]);

            foreach ($categoria->itens as $item) {
                $novaCategoria->itens()->create([
                    'codigo'     => $item->codigo,
                    'descricao'  => $item->descricao,
                    'unidade'    => $item->unidade,
                    'quantidade' => $item->quantidade,
                    'valor_mat'  => $item->valor_mat,
                    'valor_mo'   => $item->valor_mo,
                    'ordem'      => $item->ordem,
                ]);
            }
        }

        Notification::make()->title('Orçamento duplicado com sucesso')->success()->send();
    }

    // ─── PDF ─────────────────────────────────────────────────────────────────

    public function gerarPdf(int $id)
    {
        $orcamento = Orcamento::with(['projeto', 'categorias.itens'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.orcamento', ['orcamento' => $orcamento])
            ->setPaper('a4', 'portrait')
            ->setOption('isPhpEnabled', true);

        $filename = 'orcamento-' . $orcamento->data->format('Y-m-d') . '-' . Str::slug($orcamento->nome) . '.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function fecharTudo(): void
    {
        $this->modalFormAberto    = false;
        $this->modalDetalheAberto = false;
        $this->editandoId         = null;
        $this->detalheId          = null;
    }
}
