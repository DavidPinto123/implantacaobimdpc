<?php

namespace App\Filament\Pages;

use App\Models\Etapa;
use App\Models\Orcamento;
use App\Models\OrcamentoCategoria;
use App\Models\OrcamentoRevitItem;
use App\Models\Projeto;
use App\Services\OrcamentoRevitSincronizador;
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

    // ─── Filtros do detalhe ──────────────────────────────────────────────────
    public ?string $filtroCategoria = null;
    public string $filtroBusca = '';

    // ─── Campos do formulário ────────────────────────────────────────────────
    public ?int $formProjetoId = null;
    public string $formNome = '';
    public string $formNomeMkt = '';
    public string $formArquivoRevit = '';
    public ?string $formBasePrecos = null;
    public string $formData = '';
    public array $formCategorias = [];
    public bool $formRevitMudou = false;

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

    public function getResumoDetalhe(Orcamento $orcamento): array
    {
        $busca = mb_strtolower(trim($this->filtroBusca));

        $grupos = $orcamento->categorias
            ->when($this->filtroCategoria, fn (Collection $cats) => $cats->where('nome', $this->filtroCategoria))
            ->map(function (OrcamentoCategoria $categoria) use ($busca) {
                $itens = $categoria->itens->when($busca !== '', fn (Collection $itens) => $itens->filter(
                    fn ($item) => str_contains(mb_strtolower($item->descricao), $busca)
                        || str_contains(mb_strtolower((string) $item->codigo), $busca)
                ));

                return [
                    'categoria'    => $categoria,
                    'itens'        => $itens->values(),
                    'total_mat'    => $itens->sum(fn ($i) => (float) $i->quantidade * (float) $i->valor_mat),
                    'total_mo'     => $itens->sum(fn ($i) => (float) $i->quantidade * (float) $i->valor_mo),
                    'total_geral'  => $itens->sum(fn ($i) => $i->valor_total),
                ];
            })
            ->filter(fn ($grupo) => $grupo['itens']->isNotEmpty())
            ->values();

        return [
            'grupos'       => $grupos,
            'total_itens'  => $grupos->sum(fn ($g) => $g['itens']->count()),
            'total_mat'    => $grupos->sum('total_mat'),
            'total_mo'     => $grupos->sum('total_mo'),
            'total_geral'  => $grupos->sum('total_geral'),
            'chart_labels' => $grupos->pluck('categoria.nome')->toArray(),
            'chart_series' => $grupos->map(fn ($g) => round($g['total_geral'], 2))->toArray(),
        ];
    }

    public function limparFiltrosDetalhe(): void
    {
        $this->filtroCategoria = null;
        $this->filtroBusca     = '';
    }

    public function getCategoriasSugeridas(): array
    {
        return OrcamentoCategoria::query()
            ->distinct()
            ->orderBy('nome')
            ->pluck('nome')
            ->toArray();
    }

    public function getArquivosRevitPendentes(): Collection
    {
        $vinculados = Orcamento::query()
            ->whereNotNull('arquivo_revit')
            ->where('arquivo_revit', '!=', '')
            ->get(['arquivo_revit', 'base_precos'])
            ->map(fn ($o) => mb_strtolower(trim($o->arquivo_revit)) . '|' . mb_strtolower(trim((string) $o->base_precos)))
            ->all();

        return OrcamentoRevitItem::query()
            ->selectRaw('codigo_obra, base_precos, COUNT(DISTINCT categoria) as categorias, COUNT(*) as itens, MAX(atualizado_em) as ultima_atualizacao')
            ->groupBy('codigo_obra', 'base_precos')
            ->orderByDesc('ultima_atualizacao')
            ->get()
            ->reject(fn ($linha) => in_array(
                mb_strtolower(trim($linha->codigo_obra)) . '|' . mb_strtolower(trim((string) $linha->base_precos)),
                $vinculados
            ))
            ->values();
    }

    // ─── Modal: Detalhe ──────────────────────────────────────────────────────

    public function abrirDetalhe(int $id): void
    {
        $orcamento = Orcamento::find($id);

        if ($orcamento && filled($orcamento->arquivo_revit)) {
            $resultado = OrcamentoRevitSincronizador::sincronizar($orcamento);

            if ($resultado['mudou']) {
                Notification::make()
                    ->title('Orçamento atualizado a partir do Revit')
                    ->body("{$resultado['atualizados']} item(ns) atualizados, {$resultado['novos']} novo(s). Agora na revisão {$orcamento->revisao_formatada}.")
                    ->success()
                    ->send();
            }
        }

        $this->detalheId          = $id;
        $this->modalDetalheAberto = true;
        $this->limparFiltrosDetalhe();
    }

    public function fecharDetalhe(): void
    {
        $this->detalheId          = null;
        $this->modalDetalheAberto = false;
        $this->limparFiltrosDetalhe();
    }

    // ─── Modal: Formulário ───────────────────────────────────────────────────

    public function novoOrcamento(): void
    {
        $this->editandoId       = null;
        $this->formProjetoId    = $this->projetoId;
        $this->formNome         = '';
        $this->formNomeMkt      = '';
        $this->formArquivoRevit = '';
        $this->formBasePrecos   = null;
        $this->formData         = now()->format('Y-m-d');
        $this->formCategorias   = [];
        $this->formRevitMudou   = false;
        $this->resetValidation();
        $this->modalFormAberto = true;
    }

    public function editarOrcamento(int $id): void
    {
        $orcamento = Orcamento::with('categorias.itens')->findOrFail($id);

        $this->editandoId       = $id;
        $this->formProjetoId    = $orcamento->projeto_id;
        $this->formNome         = $orcamento->nome;
        $this->formNomeMkt      = $orcamento->nome_mkt ?? '';
        $this->formArquivoRevit = $orcamento->arquivo_revit ?? '';
        $this->formBasePrecos   = $orcamento->base_precos;
        $this->formData         = $orcamento->data?->format('Y-m-d') ?? '';
        $this->formRevitMudou   = false;

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
        $this->formRevitMudou  = false;
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

    private function buscarItensRevitAgrupados(string $codigoObra, ?string $basePrecos = null): Collection
    {
        return OrcamentoRevitItem::where('codigo_obra', $codigoObra)
            ->when(
                $basePrecos !== null,
                fn ($query) => $query->where('base_precos', $basePrecos),
                fn ($query) => $query->whereNull('base_precos')
            )
            ->orderBy('categoria')
            ->orderBy('ordem')
            ->get()
            ->groupBy('categoria');
    }

    public function sincronizarRevit(): void
    {
        $codigoObra = trim($this->formArquivoRevit);

        if (! $codigoObra) {
            Notification::make()
                ->title('Informe o nome do arquivo Revit antes de sincronizar.')
                ->warning()
                ->send();

            return;
        }

        $itensPorCategoria = $this->buscarItensRevitAgrupados($codigoObra, $this->formBasePrecos);

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
                    $itemAtual = $this->formCategorias[$catIndex]['itens'][$itemIndex];
                    $mudou = $itemAtual['descricao'] !== $dadosItem['descricao']
                        || $itemAtual['unidade'] !== $dadosItem['unidade']
                        || (string) $itemAtual['quantidade'] !== $dadosItem['quantidade']
                        || (string) $itemAtual['valor_mat'] !== $dadosItem['valor_mat']
                        || (string) $itemAtual['valor_mo'] !== $dadosItem['valor_mo'];

                    if ($mudou) {
                        $this->formCategorias[$catIndex]['itens'][$itemIndex] = ['id' => $itemAtual['id'] ?? null, ...$dadosItem];
                        $atualizados++;
                    }
                } else {
                    $this->formCategorias[$catIndex]['itens'][] = ['id' => null, ...$dadosItem];
                    $adicionados++;
                }
            }
        }

        $this->formRevitMudou = $this->formRevitMudou || ($adicionados + $atualizados) > 0;

        Notification::make()
            ->title('Sincronizado com o Revit')
            ->body("{$adicionados} itens novos, {$atualizados} atualizados. Revise e clique em Salvar para confirmar.")
            ->success()
            ->send();
    }

    public function criarProjetoAutomaticoRevit(string $codigoObra, ?string $basePrecos = null): void
    {
        $codigoObra = trim($codigoObra);

        $jaExiste = Orcamento::where('arquivo_revit', $codigoObra)
            ->when(
                $basePrecos !== null,
                fn ($query) => $query->where('base_precos', $basePrecos),
                fn ($query) => $query->whereNull('base_precos')
            )
            ->exists();

        if ($jaExiste) {
            Notification::make()
                ->title('Já existe um orçamento vinculado a este arquivo.')
                ->warning()
                ->send();

            return;
        }

        $itensPorCategoria = $this->buscarItensRevitAgrupados($codigoObra, $basePrecos);

        if ($itensPorCategoria->isEmpty()) {
            Notification::make()
                ->title("Nenhum item do Revit encontrado para \"{$codigoObra}\"")
                ->warning()
                ->send();

            return;
        }

        // Mesmo codigo_obra pode já ter um orçamento de outra base (LPU/SINAPI) — nesse
        // caso reaproveita o Projeto já criado em vez de duplicar.
        $projeto = Projeto::whereHas(
            'orcamentos',
            fn ($query) => $query->where('arquivo_revit', $codigoObra)
        )->first();

        if (! $projeto) {
            $projeto = Projeto::create([
                'nome'     => $codigoObra,
                'user_id'  => auth()->id(),
                'etapa_id' => Etapa::where('nome', 'Prospecção')->value('id'),
            ]);
        }

        $nomeOrcamento = $basePrecos ? "Orçamento Revit - {$codigoObra} ({$basePrecos})" : "Orçamento Revit - {$codigoObra}";

        $orcamento = Orcamento::create([
            'projeto_id'    => $projeto->id,
            'nome'          => $nomeOrcamento,
            'arquivo_revit' => $codigoObra,
            'base_precos'   => $basePrecos,
            'data'          => now()->format('Y-m-d'),
            'criado_por'    => auth()->id(),
        ]);

        OrcamentoRevitSincronizador::sincronizar($orcamento, bumpRevisao: false);

        Notification::make()
            ->title('Orçamento criado')
            ->body("\"{$nomeOrcamento}\" criado a partir do arquivo Revit, vinculado ao projeto \"{$projeto->nome}\".")
            ->success()
            ->send();

        $this->selecionarProjeto($projeto->id, $projeto->nome);
    }

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function salvar(): void
    {
        $this->validate([
            'formProjetoId'                        => 'required|exists:projetos,id',
            'formNome'                              => 'required|string|max:255',
            'formNomeMkt'                           => 'nullable|string|max:255',
            'formArquivoRevit'                      => 'nullable|string|max:255',
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
            'projeto_id'    => $this->formProjetoId,
            'nome'          => $this->formNome,
            'nome_mkt'      => $this->formNomeMkt ?: null,
            'arquivo_revit' => $this->formArquivoRevit ?: null,
            'base_precos'   => $this->formBasePrecos ?: null,
            'data'          => $this->formData,
        ];

        if ($this->editandoId) {
            $orcamento = Orcamento::findOrFail($this->editandoId);

            if ($this->formRevitMudou) {
                $dados['revisao']              = $orcamento->revisao + 1;
                $dados['revit_sincronizado_em'] = now();
            }

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
