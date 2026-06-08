<?php

namespace App\Filament\Resources\ElaboracaoAditivos\Pages;

use App\Filament\Resources\ElaboracaoAditivos\ElaboracaoAditivoResource;
use App\Models\Construtora;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class CreateAditivo extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string $resource = ElaboracaoAditivoResource::class;

    protected string $view = 'filament.resources.elaboracao-aditivos.pages.create-aditivo';

    public ?array $data = [];

    public array $itens = [];

    public function getTitle(): string
    {
        return 'Criação de aditivos';
    }

    public function getBreadcrumb(): string
    {
        return 'Criação de aditivos';
    }

    public function getHeading(): string
    {
        return 'Criação de aditivos';
    }

    public function mount(): void
    {
        $user = Auth::user();
        $construtoraId = $user?->construtoras_id;
        $construtoraNome = null;

        if ($construtoraId) {
            $construtora = Construtora::find($construtoraId);
            $construtoraNome = $construtora?->nome;
        }

        $this->form->fill([
            'obra_id' => null,
            'gestor_id' => null,
            'data' => now()->format('Y-m-d'),
            'escopo_id' => null,
            'construtora_id' => $construtoraId,
            'construtora_nome' => $construtoraNome ?: 'Não definida',
            'foto_antes' => [],
            'foto_depois' => [],
            'projeto_orcado' => [],
            'projeto_revisado' => [],
            'escopo_contratado' => [],
            'escopo_real' => [],
        ]);

        $this->itens = [
            $this->novaLinha('1.1'),
        ];
    }

    protected function novaLinha(string $item = ''): array
    {
        return [
            'item' => $item,
            'descricao_servico' => '',
            'quantidade' => 0,
            'unidade' => '',
            'valor_material_unitario' => 0,
            'valor_mao_obra_unitario' => 0,
            'total_unitario' => 0,
            'valor_total_geral' => 0,
        ];
    }

    public function addLinha(): void
    {
        $proximo = count($this->itens) + 1;
        $this->itens[] = $this->novaLinha("1.$proximo");
    }

    public function removeLinha(int $index): void
    {
        unset($this->itens[$index]);
        $this->itens = array_values($this->itens);

        $this->reindexarItens();
        $this->recalcularTudo();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Expansão/ Orçamentos')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('obra_id')
                                    ->label('Obra')
                                    ->options(fn (): array => Obras::query()
                                        ->orderBy('unidade')
                                        ->get(['id', 'unidade'])
                                        ->mapWithKeys(fn (Obras $obra): array => [
                                            $obra->id => (string) ($obra->unidade ?: "Obra #{$obra->id}"),
                                        ])
                                        ->all())
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set): null => $set('escopo_id', null)),
                                Select::make('gestor_id')
                                    ->label('Gestor')
                                    ->options(fn (): array => User::query()
                                        ->orderBy('name')
                                        ->whereHas('roles', fn ($query) => $query->where('name', 'Gestor'))
                                        ->whereHas('setores', fn ($query) => $query->where('setor', 'Obras'))
                                        ->get(['id', 'name'])
                                        ->mapWithKeys(fn (User $gestor): array => [
                                            $gestor->id => (string) ($gestor->name ?: "Usuário #{$gestor->id}"),
                                        ])
                                        ->all())
                                    ->required()
                                    ->searchable(),
                                DatePicker::make('data')
                                    ->label('Data')
                                    ->native(true)
                                    ->required(),
                                Select::make('escopo_id')
                                    ->label('Ref. serviço')
                                    ->options(fn (Get $get): array => ElaboracaoAditivoResource::opcoesRefServicoPorObra((int) $get('obra_id')))
                                    ->required()
                                    ->disabled(fn (Get $get): bool => blank($get('obra_id')))
                                    ->searchable(),
                                TextInput::make('construtora_nome')
                                    ->label('Fornecedor')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Evidências')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('foto_antes')
                                    ->label('Foto antes')
                                    ->multiple()
                                    ->panelLayout('grid')->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory('asa/anexos/foto-antes')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(10240)
                                    ->hintIcon('heroicon-m-information-circle')
                                    ->hintIconTooltip('Formatos permitidos: JPG, JPEG, PNG, WEBP. Tamanho máximo: 10MB.'),
                                FileUpload::make('foto_depois')
                                    ->label('Foto depois (caso já executado)')
                                    ->multiple()
                                    ->panelLayout('grid')->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory('asa/anexos/foto-depois')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(10240)
                                    ->hintIcon('heroicon-m-information-circle')
                                    ->hintIconTooltip('Formatos permitidos: JPG, JPEG, PNG, WEBP. Tamanho máximo: 10MB.'),
                                FileUpload::make('projeto_orcado')
                                    ->label('Projeto orçado')
                                    ->multiple()
                                    ->panelLayout('grid')->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory('asa/anexos/projeto-orcado')
                                    ->preserveFilenames()
                                    ->maxSize(716800)
                                    ->hintIcon('heroicon-m-information-circle')
                                    ->hintIconTooltip('Formatos permitidos: RVT, PDF e DWG. Tamanho máximo: 700MB.'),
                                FileUpload::make('projeto_revisado')
                                    ->label('Projeto revisado')
                                    ->multiple()
                                    ->panelLayout('grid')->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory('asa/anexos/projeto-revisado')
                                    ->preserveFilenames()
                                    ->maxSize(716800)
                                    ->hintIcon('heroicon-m-information-circle')
                                    ->hintIconTooltip('Formatos permitidos: RVT, PDF e DWG. Tamanho máximo: 700MB.'),
                                FileUpload::make('escopo_contratado')
                                    ->label('Escopo contratado')
                                    ->multiple()
                                    ->panelLayout('grid')->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory('asa/anexos/escopo-contratado')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->rules(['mimes:pdf,doc,docx,xls,xlsx'])
                                    ->maxSize(10240)
                                    ->hintIcon('heroicon-m-information-circle')
                                    ->hintIconTooltip('Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX. Tamanho máximo: 10MB.'),
                                FileUpload::make('escopo_real')
                                    ->label('Escopo real')
                                    ->multiple()
                                    ->panelLayout('grid')->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory('asa/anexos/escopo-real')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->rules(['mimes:pdf,doc,docx,xls,xlsx'])
                                    ->maxSize(10240)
                                    ->hintIcon('heroicon-m-information-circle')
                                    ->hintIconTooltip('Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX. Tamanho máximo: 10MB.'),
                            ]),
                    ]),
            ]);
    }

    public function updatedDataObraId($value): void
    {
        if (! $value) {
            $this->data['gestor_id'] = null;

            return;
        }

        $obra = Obras::find($value);

        if (! $obra || blank($obra->engenharia)) {
            $this->data['gestor_id'] = null;

            return;
        }

        $gestorNome = trim($obra->engenharia);

        $gestor = User::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($gestorNome)])
            ->first();

        $this->data['gestor_id'] = $gestor?->id;
    }

    public function updated($name): void
    {
        if (preg_match('/itens\.(\d+)\.(quantidade|valor_material_unitario|valor_mao_obra_unitario)/', $name, $matches)) {
            $index = (int) $matches[1];
            $this->recalcularLinha($index);
        }
    }

    protected function reindexarItens(): void
    {
        foreach ($this->itens as $i => &$item) {
            $item['item'] = '1.'.($i + 1);
        }
    }

    protected function recalcularLinha(int $index): void
    {
        $qtd = (float) ($this->itens[$index]['quantidade'] ?? 0);
        $mat = (float) ($this->itens[$index]['valor_material_unitario'] ?? 0);
        $mo = (float) ($this->itens[$index]['valor_mao_obra_unitario'] ?? 0);

        $totalUnitario = $mat + $mo;
        $valorTotalGeral = $qtd * $totalUnitario;

        $this->itens[$index]['total_unitario'] = round($totalUnitario, 2);
        $this->itens[$index]['valor_total_geral'] = round($valorTotalGeral, 2);
    }

    protected function recalcularTudo(): void
    {
        foreach (array_keys($this->itens) as $index) {
            $this->recalcularLinha($index);
        }
    }

    public function getTotalGeralProperty(): float
    {
        return collect($this->itens)->sum(fn ($item) => (float) ($item['valor_total_geral'] ?? 0));
    }

    public function save(): void
    {
        try {
            $this->form->getState();

            $this->validate([
                'data.obra_id' => ['required'],
                'data.gestor_id' => ['required'],
                'data.data' => ['required', 'date'],
                'data.escopo_id' => [
                    'required',
                    Rule::in(array_keys(ElaboracaoAditivoResource::opcoesRefServicoPorObra((int) ($this->data['obra_id'] ?? 0)))),
                ],
                'itens' => ['required', 'array', 'min:1'],
                'itens.*.descricao_servico' => ['required', 'string'],
                'itens.*.quantidade' => ['required', 'numeric', 'min:0'],
                'itens.*.unidade' => ['required', 'string'],
                'itens.*.valor_material_unitario' => ['required', 'numeric', 'min:0'],
                'itens.*.valor_mao_obra_unitario' => ['required', 'numeric', 'min:0'],
            ], [
                'data.obra_id.required' => 'O campo obra é obrigatório.',
                'data.gestor_id.required' => 'O campo gestor é obrigatório.',
                'data.data.required' => 'O campo data é obrigatório.',
                'data.escopo_id.required' => 'O campo ref. serviço é obrigatório.',
                'data.escopo_id.in' => 'Selecione um ref. serviço cadastrado na obra escolhida.',
                'itens.*.descricao_servico.required' => 'Preencha a descrição do serviço em todos os itens.',
                'itens.*.quantidade.required' => 'Preencha a quantidade em todos os itens.',
                'itens.*.unidade.required' => 'Preencha a unidade em todos os itens.',
            ]);
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Não foi possível salvar')
                ->body($e->validator->errors()->first() ?: 'Existem campos inválidos na planilha.')
                ->danger()
                ->send();

            throw $e;
        }

        try {
            $this->recalcularTudo();

            $aditivo = ElaboracaoAditivo::create([
                'user_id' => Auth::id(),
                'obra_id' => $this->data['obra_id'],
                'gestor_id' => $this->data['gestor_id'],
                'data' => $this->data['data'],
                'as_escopo_id' => $this->data['escopo_id'],
                'construtora_id' => $this->data['construtora_id'],
                'total_geral' => $this->totalGeral,
                'status_fluxo' => 'elaboracao',
                'foto_antes' => blank($this->data['foto_antes'] ?? []) ? null : $this->data['foto_antes'],
                'foto_depois' => blank($this->data['foto_depois'] ?? []) ? null : $this->data['foto_depois'],
                'projeto_orcado' => blank($this->data['projeto_orcado'] ?? []) ? null : $this->data['projeto_orcado'],
                'projeto_revisado' => blank($this->data['projeto_revisado'] ?? []) ? null : $this->data['projeto_revisado'],
                'escopo_contratado' => blank($this->data['escopo_contratado'] ?? []) ? null : $this->data['escopo_contratado'],
                'escopo_real' => blank($this->data['escopo_real'] ?? []) ? null : $this->data['escopo_real'],
            ]);

            foreach ($this->itens as $item) {
                $aditivo->itens()->create([
                    'item' => $item['item'],
                    'descricao_servico' => $item['descricao_servico'],
                    'quantidade' => $item['quantidade'],
                    'unidade' => $item['unidade'],
                    'valor_material_unitario' => $item['valor_material_unitario'],
                    'valor_mao_obra_unitario' => $item['valor_mao_obra_unitario'],
                    'total_unitario' => $item['total_unitario'],
                    'valor_total_geral' => $item['valor_total_geral'],
                ]);
            }

            Notification::make()
                ->title('Aditivo salvo com sucesso.')
                ->success()
                ->send();

            $this->redirect(static::getResource()::getUrl('visualizar', [
                'record' => $aditivo,
            ]));
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar aditivo', [
                'message' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Erro ao salvar aditivo')
                ->body('Não foi possível salvar. Verifique os dados e tente novamente.')
                ->danger()
                ->send();

            return;
        }
    }
}
