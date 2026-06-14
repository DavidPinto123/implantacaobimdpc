<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Models\CapexDisciplina;
use App\Models\ControlePedido;
use App\Models\OrdemInvestimento;
use App\Models\Projeto;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class SimuladorCapex extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.pages.simulador-capex';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Simulador OI';

    protected static ?string $title = 'Simulador OI';

    protected static ?int $navigationSort = 1;

    protected static string|null|UnitEnum $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Orçamentos';

    public ?array $data = [];

    public array $linhas = [];

    public bool $modoEdicao = false;

    public ?int $projeto_id = null;

    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->form->fill([
            'fator_correcao' => 1,
            'area_unidade' => 1200,
        ]);

        $this->carregarEstrutura();
    }

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([

                Section::make('Configuração')
                    ->columns(4)
                    ->schema([

                        Select::make('projeto_id')
                            ->label('Projeto')
                            ->options(Projeto::orderBy('nome')->pluck('nome', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->columnSpan(2)
                            ->afterStateUpdated(fn ($state) => $this->projeto_id = $state),

                        TextInput::make('fator_correcao')->numeric()->default(1)->live(),
                        TextInput::make('area_unidade')->numeric()->default(1200)->live(),

                        Toggle::make('bts')->live(),
                        Toggle::make('mall')->live(),
                        Toggle::make('entrada_energia')->live(),
                        Toggle::make('elevador')->live(),

                        Select::make('vestiario')
                            ->label('Vestiários')
                            ->options(['P' => 'P', 'M' => 'M', 'G' => 'G', 'GG' => 'GG']),

                        Select::make('tamanho_fachada')
                            ->label('Qual o tamanho da fachada ?')
                            ->options(['P' => 'P', 'M' => 'M', 'G' => 'G', 'GG' => 'GG']),

                    ]),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION: INSERIR GRUPO
    |--------------------------------------------------------------------------
    */

    public function inserirGrupoAction(): Action
    {
        return Action::make('inserirGrupo')
            ->label('Inserir Item')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->visible(fn () => $this->modoEdicao)
            ->modalHeading('Novo Item')
            ->form([
                TextInput::make('grupo')->required(),
                TextInput::make('padrao')->numeric(),
                TextInput::make('ad')->numeric(),
                Textarea::make('consideracoes'),
            ])
            ->action(function (array $data) {

                $this->linhas[] = [
                    'grupo' => $data['grupo'],
                    'nome' => 'Item Inicial',
                    'padrao' => $data['padrao'] ?? 0,
                    'ad' => $data['ad'] ?? 0,
                    'consideracoes' => $data['consideracoes'] ?? '',
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION: INSERIR SUBITEM
    |--------------------------------------------------------------------------
    */

    public function inserirSubitemAction(): Action
    {
        return Action::make('inserirSubitem')
            ->label('Inserir Subitem')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->visible(fn () => $this->modoEdicao)
            ->modalHeading('Novo Subitem')
            ->form([
                TextInput::make('nome')->required(),
                TextInput::make('padrao')->numeric(),
                TextInput::make('ad')->numeric(),
                Textarea::make('consideracoes'),
            ])
            ->action(function (array $data, array $arguments) {

                $grupo = $arguments['grupo'] ?? null;
                if (! $grupo) {
                    return;
                }

                $this->linhas[] = [
                    'grupo' => $grupo,
                    'nome' => $data['nome'],
                    'padrao' => $data['padrao'] ?? 0,
                    'ad' => $data['ad'] ?? 0,
                    'consideracoes' => $data['consideracoes'] ?? '',
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | EDIÇÃO
    |--------------------------------------------------------------------------
    */

    public function habilitarEdicao()
    {
        $this->modoEdicao = true;
    }

    public function cancelarEdicao()
    {
        $this->modoEdicao = false;
        $this->carregarEstrutura(); // recarrega valores originais
    }

    public function salvar()
    {
        $this->modoEdicao = false;

        Notification::make()
            ->title('Simulação atualizada com sucesso!')
            ->success()
            ->send();
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULADO
    |--------------------------------------------------------------------------
    */

    public function getTotalGeralProperty(): float
    {
        return collect($this->linhas)->sum(function ($linha) {

            $padrao = is_numeric($linha['padrao'] ?? null)
                ? (float) $linha['padrao']
                : 0;

            $ad = is_numeric($linha['ad'] ?? null)
                ? (float) $linha['ad']
                : 0;

            return $padrao + $ad;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ESTRUTURA
    |--------------------------------------------------------------------------
    */

    private function carregarEstrutura(): void
    {
        $this->linhas = [];

        $disciplinas = CapexDisciplina::with('children')
            ->whereNull('parent_id')
            ->where('ativo', true)
            ->get();

        foreach ($disciplinas as $grupo) {
            foreach ($grupo->children as $child) {

                $valorPadrao = $this->calcularValor($child);

                $this->linhas[] = [
                    'key' => uniqid(),
                    'grupo' => $grupo->nome,
                    'nome' => $child->nome,
                    'padrao' => $valorPadrao, // 🔥 AQUI ESTÁ A CORREÇÃO
                    'ad' => 0,
                    'consideracoes' => $child->consideracoes,
                ];
            }
        }
    }

    public function calcularDisciplinas(): array
    {
        $disciplinas = CapexDisciplina::where('ativo', true)->get();

        $resultado = [];
        $totalBase = 0;

        foreach ($disciplinas as $disciplina) {

            $valor = 0;

            switch ($disciplina->tipo_calculo) {

                case 'area':
                    $valor = ($this->data['area_unidade'] ?? 0) * $disciplina->valor_base;
                    break;

                case 'fixo':
                    $valor = $disciplina->valor_base;
                    break;

                case 'percentual':
                    $valor = $totalBase * ($disciplina->valor_base / 100);
                    break;
            }

            if ($disciplina->usa_fator_correcao) {
                $valor *= ($this->data['fator_correcao'] ?? 1);
            }

            $totalBase += $valor;

            $resultado[] = [
                'nome' => $disciplina->nome,
                'valor' => $valor,
            ];
        }

        return $resultado;
    }

    public function getCustoMetroQuadradoProperty(): float
    {
        $area = (float) ($this->data['area_unidade'] ?? 0);

        if ($area <= 0) {
            return 0;
        }

        return $this->totalGeral / $area;
    }

    public function getEstruturaProperty()
    {
        $principais = CapexDisciplina::with('children')
            ->whereNull('parent_id')
            ->where('ativo', true)
            ->get();
        foreach ($principais as $disciplina) {

            $filhos = [];
            $totalFilhos = 0;

            foreach ($disciplina->children as $child) {

                $valorPadrao = $this->calcularValor($child);
                $valorAd = 0;
                $valorTotal = $valorPadrao + $valorAd;

                $filhos[] = [
                    'id' => $child->id,
                    'nome' => $child->nome,
                    'padrao' => $valorPadrao,
                    'ad' => $valorAd,
                    'total' => $valorTotal,
                    'consideracoes' => $child->consideracoes,
                ];

                $totalFilhos += $valorTotal;
            }

            $estrutura[] = [
                'id' => $disciplina->id,
                'nome' => $disciplina->nome,
                'filhos' => $filhos,
                'total' => $totalFilhos,
            ];
        }

        return $estrutura;
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORTAR PDF
    |--------------------------------------------------------------------------
    */

    public function exportarPdf()
    {
        $projetoNome = null;

        if ($this->projeto_id) {
            $projeto = Projeto::find($this->projeto_id);
            $projetoNome = $projeto?->nome;
        }

        $pdf = Pdf::loadView('pdf.simulador-capex', [
            'linhas' => $this->linhas,
            'totalGeral' => $this->totalGeral,
            'usuario' => Auth::user(),
            'projeto' => $projetoNome,
            'area' => $this->data['area_unidade'] ?? 0,
            'dataHora' => now(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'simulador-capex.pdf'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GERAR OI
    |--------------------------------------------------------------------------
    */

    public function gerarOi()
    {
        if (! $this->projeto_id) {
            Notification::make()
                ->title('Selecione um projeto antes de gerar a OI.')
                ->danger()
                ->send();

            return;
        }

        $projeto = Projeto::findOrFail($this->projeto_id);

        // -----------------------------
        // GERAR PDF
        // -----------------------------

        $pdf = Pdf::loadView('pdf.simulador-capex', [
            'linhas' => $this->linhas,
            'totalGeral' => $this->totalGeral,
            'area' => $this->data['area_unidade'] ?? 0,
            'projeto' => $projeto->nome,
            'usuario' => Auth::user(),
            'dataHora' => now(),
        ])->setPaper('a4', 'landscape');

        $nomeArquivo = 'oi_capex_'.$projeto->id.'_'.now()->timestamp.'.pdf';
        $caminho = "projetos/{$projeto->id}/{$nomeArquivo}";

        Storage::disk((string) config('filesystems.media_disk', 'r2'))->put($caminho, $pdf->output());

        // -----------------------------
        // CRIAR OI
        // -----------------------------

        $oi = OrdemInvestimento::create([
            'projeto_id' => $projeto->id,
            'valor_total' => $this->totalGeral,
            'area' => $this->data['area_unidade'] ?? 0,
            'custo_m2' => $this->custoMetroQuadrado,
            'estrutura' => $this->linhas,
            'pdf_path' => $caminho,
            'status_oi' => 'em_aprovacao',
            'user_id' => Auth::id(),
        ]);

        // -----------------------------
        // CRIAR CONTROLE DE PEDIDO
        // -----------------------------

        // Montar todos pedidos como false
        $pedidos = [];

        foreach (ControlePedidoResource::pedidosMap() as $nome => $codigos) {
            $codigo = $codigos[0];
            $codigoKey = str_replace('.', '_', $codigo);

            $pedidos[$codigoKey] = false;
        }

        ControlePedido::create([
            'projeto_id' => $projeto->id,
            'valor_oi' => $this->totalGeral,
            'saldo' => $this->totalGeral,
            'valor_realizado' => 0,
            'pedidos' => $pedidos,
            'status' => 'provisorio', // default do seu form
            'situacao' => 'em_processo', // default
        ]);

        // -----------------------------
        // Atualizar projeto
        // -----------------------------

        $projeto->update([
            'oi_pdf' => $caminho,
        ]);

        Notification::make()
            ->title('OI e Controle de Pedido criados com sucesso.')
            ->success()
            ->send();
    }

    private function calcularValor($disciplina): float
    {
        $area = $this->data['area_unidade'] ?? 0;
        $fator = $this->data['fator_correcao'] ?? 1;

        $valor = match ($disciplina->tipo_calculo) {
            'area' => $area * $disciplina->valor_base,
            'fixo' => $disciplina->valor_base,
            default => 0,
        };

        if ($disciplina->usa_fator_correcao) {
            $valor *= $fator;
        }

        return $valor;
    }
}
