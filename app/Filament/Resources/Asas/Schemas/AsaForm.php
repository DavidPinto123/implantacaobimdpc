<?php

namespace App\Filament\Resources\Asas\Schemas;

use App\Enums\AsStatus;
use App\Exports\ElaboracaoAditivoPlanilhaExport;
use App\Filament\Components\Forms\MoneyInput;
use App\Models\Asa;
use App\Models\Projeto;
use App\Services\AsaService;
use App\Support\AsaAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Maatwebsite\Excel\Facades\Excel;

class AsaForm
{
    private static function asaDirectory(?Asa $record, string $suffix): string
    {
        return filled($record?->id)
            ? 'asa/'.$record->id.'/'.$suffix
            : 'asa/tmp/'.$suffix;
    }

    private static function shouldLockFields(?Asa $record): bool
    {
        if (blank($record?->id)) {
            return false;
        }

        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return AsaAccess::shouldRestrictEditingToDesconto($user);
    }

    private static function dispatchAutosave(Livewire $livewire): void
    {
        if (method_exists($livewire, 'autoSaveCurrentState')) {
            $livewire->autoSaveCurrentState();
        }
    }

    private static function autosaveOnBlur(mixed $component): mixed
    {
        return $component
            ->live(onBlur: true)
            ->skipRenderAfterStateUpdated()
            ->afterStateUpdated(fn (Livewire $livewire) => self::dispatchAutosave($livewire));
    }

    private static function autosaveOnChange(mixed $component): mixed
    {
        return $component
            ->live()
            ->skipRenderAfterStateUpdated()
            ->afterStateUpdated(fn (Livewire $livewire) => self::dispatchAutosave($livewire));
    }

    private static function abrirModalShellQuandoNecessario(mixed $state, Livewire $livewire): void
    {
        if ($state !== 'Shell' || ! method_exists($livewire, 'mountAction')) {
            return;
        }

        $livewire->mountAction('registrarNegociacaoShell');
    }

    private static function registrarContratoAnteriorShell(mixed $state, mixed $old, Livewire $livewire): void
    {
        if ($state !== 'Shell' || ! method_exists($livewire, 'registrarContratoAnteriorDaNegociacaoShell')) {
            return;
        }

        $livewire->registrarContratoAnteriorDaNegociacaoShell(is_string($old) ? $old : null);
    }

    private static function limparNegociacaoShellQuandoNecessario(mixed $state, callable $set, ?Asa $record): void
    {
        if ($state === 'Shell') {
            return;
        }

        $set('shell_cabe_como_negociacao', false);
        $set('shell_justificativa_negociacao', null);

        if (filled($record?->id)) {
            $record->update([
                'contrato' => $state,
                'shell_cabe_como_negociacao' => false,
                'shell_justificativa_negociacao' => null,
            ]);
        }
    }

    private static function autosaveFileUpload(FileUpload $component): FileUpload
    {
        return $component;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ASA')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->description('AUTORIZAÇÃO DE SERVIÇO ADICIONAL')
                    ->schema([
                        Group::make()
                            ->schema([
                                self::autosaveOnBlur(
                                    TextInput::make('numero_asa')
                                        ->label('Número da ASA')
                                        ->disabled()
                                        ->dehydrated()
                                        ->rules(['required'])
                                        ->markAsRequired()
                                        ->maxLength(255)
                                        ->columnSpanFull()
                                        ->unique(ignoreRecord: true)
                                        ->validationMessages([
                                            'required' => 'Campo Obrigatório',
                                        ]),
                                ),

                                Select::make('projeto_id')
                                    ->label('Unidade')
                                    ->relationship('projeto', 'nome')
                                    ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull()
                                    ->partiallyRenderComponentsAfterStateUpdated(['sigla', 'endereco'])
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, Livewire $livewire) {
                                        $projeto = Projeto::find($state);

                                        $set('sigla', $projeto?->sigla);
                                        $set('endereco', $projeto?->endereco);

                                        self::dispatchAutosave($livewire);
                                    }),
                            ])
                            ->columns(3),

                        Group::make()
                            ->schema([
                                TextInput::make('sigla')
                                    ->label('Sigla')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('endereco')
                                    ->label('Endereço')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('gestor_exibicao')
                                    ->label('Gestor')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->gestor?->name ?? '-'),

                                TextInput::make('gestor_id')
                                    ->default(fn () => Auth::id())
                                    ->hidden()
                                    ->dehydrated()
                                    ->hiddenLabel()
                                    ->extraInputAttributes(['class' => 'hidden']),

                                self::autosaveOnBlur(
                                    TextInput::make('solicitante')
                                        ->label('Solicitante')
                                        ->disabled()
                                        ->dehydrated()
                                        ->required()
                                        ->maxLength(255),
                                ),

                                Select::make('contrato')
                                    ->label('Origem da alteração')
                                    ->native(false)
                                    ->partiallyRenderComponentsAfterStateUpdated([
                                        'numero_asa',
                                        'shell_cabe_como_negociacao',
                                        'shell_justificativa_negociacao',
                                    ])
                                    ->live()
                                    ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                    ->options([
                                        'Projetos' => 'Projetos',
                                        'Cliente' => 'Cliente',
                                        'Legalização' => 'Legalização',
                                        'Shell' => 'Shell',
                                        'Orçamentos' => 'Orçamentos',
                                    ])
                                    ->afterStateUpdated(function ($state, $old, callable $set, ?Asa $record, Livewire $livewire) {
                                        self::registrarContratoAnteriorShell($state, $old, $livewire);
                                        self::limparNegociacaoShellQuandoNecessario($state, $set, $record);

                                        // Para ASAs criadas a partir de aditivo, o número depende da origem (A/C).
                                        if (! $record || blank($record->elaboracao_aditivo_id)) {
                                            self::dispatchAutosave($livewire);
                                            self::abrirModalShellQuandoNecessario($state, $livewire);

                                            return;
                                        }

                                        $asaService = app(AsaService::class);
                                        $set('numero_asa', $asaService->gerarNumeroAsaParaAsa($record, $state));
                                        self::dispatchAutosave($livewire);
                                        self::abrirModalShellQuandoNecessario($state, $livewire);
                                    }),

                                self::autosaveOnChange(
                                    ToggleButtons::make('shell_cabe_como_negociacao')
                                        ->label('Cabe negociação com proprietário')
                                        ->options([
                                            1 => 'Sim',
                                            0 => 'Não',
                                        ])
                                        ->colors([
                                            1 => 'success',
                                            0 => 'danger',
                                        ])
                                        ->inline()
                                        ->visible(fn (callable $get): bool => $get('contrato') === 'Shell')
                                        ->required(fn (callable $get): bool => $get('contrato') === 'Shell')
                                        ->disabled(fn (?Asa $record): bool => self::shouldLockFields($record)),
                                ),

                                self::autosaveOnBlur(
                                    Textarea::make('shell_justificativa_negociacao')
                                        ->label('Justifique')
                                        ->visible(fn (callable $get): bool => $get('contrato') === 'Shell')
                                        ->disabled(fn (?Asa $record): bool => self::shouldLockFields($record))
                                        ->required(fn (callable $get): bool => $get('contrato') === 'Shell')
                                        ->rows(3)
                                        ->maxLength(3000)
                                        ->columnSpanFull(),
                                ),

                                self::autosaveOnBlur(
                                    TextInput::make('subgrupo')
                                        ->label('Grupo')
                                    // Para ASA gerada do aditivo, o grupo já vem preenchido e não deve ser editado.
                                        ->disabled(fn (?Asa $record) => filled($record?->elaboracao_aditivo_id) || self::shouldLockFields($record))
                                        ->dehydrated(fn (?Asa $record) => blank($record?->elaboracao_aditivo_id) && ! self::shouldLockFields($record)),
                                ),
                            ])
                            ->columns(2),
                        /*
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                //'rascunho' => 'Rascunho',
                                'em_analise' => 'Em análise',
                                'aprovado' => 'Aprovado',
                                'reprovado' => 'Reprovado',
                                //'cancelado' => 'Cancelado',
                            ])
                            //->default('rascunho')
                            ->required()
                            ->native(false)
                            ->live(),
                        */

                        TextInput::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (AsStatus|string|null $state): string => $state instanceof AsStatus
                                ? $state->label()
                                : AsStatus::labelFrom($state))
                            ->disabled()
                            ->dehydrated(false),

                        self::autosaveOnBlur(
                            TextInput::make('codigo_as_emitida')
                                ->label('Código AS emitida')
                                ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                ->visible(fn (callable $get) => $get('status') === AsStatus::APROVADO->value)
                                ->required(fn (callable $get) => $get('status') === AsStatus::APROVADO->value),
                        ),

                        Group::make()
                            ->schema([
                                self::autosaveOnChange(
                                    DatePicker::make('data_solicitacao')
                                        ->label('Data da solicitação')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->displayFormat('d/m/Y'),
                                ),

                                DatePicker::make('data_aprovacao')
                                    ->label('Data da aprovação')
                                    ->displayFormat('d/m/Y')
                                    // Deve ser preenchida apenas pelo fluxo de aprovação (gestor/orçamento).
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->columns(2),

                        self::autosaveOnBlur(
                            TextInput::make('descricao')
                                ->label('Descrição')
                                ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                ->required()
                                ->columnSpanFull(),
                        ),

                        self::autosaveOnBlur(
                            RichEditor::make('justificativa')
                                ->label('Justificativa')
                                ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                ->helperText('Motivo que gerou o serviço adicional.')
                                ->columnSpanFull(),
                        ),

                    ])->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('Valores')
                            ->schema([
                                self::autosaveOnBlur(
                                    MoneyInput::make('valor_bruto', 'Valor bruto')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record)),
                                ),

                                MoneyInput::make('desconto', 'Desconto')
                                    ->partiallyRenderComponentsAfterStateUpdated(['valor_total'])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set, Livewire $livewire) {
                                        $valorBruto = MoneyInput::parse($get('valor_bruto') ?? 0) ?? 0;
                                        $desconto = MoneyInput::parse($state ?? 0) ?? 0;
                                        $total = max($valorBruto - $desconto, 0);
                                        $set('valor_total', MoneyInput::formatBr($total));

                                        self::dispatchAutosave($livewire);
                                    }),

                                MoneyInput::make('valor_total', 'Valor total')
                                    ->disabled(),
                            ]),

                        Section::make('Anexos')
                            ->compact()
                            ->schema([
                                self::autosaveFileUpload(
                                    FileUpload::make('evidencias')
                                        ->label('Evidências / anexos')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->multiple()
                                        ->panelLayout('grid')
                                        ->disk((string) config('filesystems.media_disk', 'r2'))
                                        ->directory(fn (?Asa $record) => self::asaDirectory($record, 'evidencias')),
                                ),

                                self::autosaveOnBlur(
                                    Textarea::make('observacoes')
                                        ->label('Observações')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->rows(5),
                                ),
                            ]),

                    ])
                    ->columnSpan(1),

                Section::make('Planilha de aditivos')
                    ->headerActions([
                        Action::make('exportarExcel')
                            ->label('Exportar Excel')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('success')
                            ->visible(fn (?Asa $record) => filled($record?->elaboracao_aditivo_id))
                            ->action(function (?Asa $record) {
                                $aditivo = $record?->elaboracaoAditivo;

                                if (! $aditivo) {
                                    Notification::make()
                                        ->title('Aditivo não encontrado')
                                        ->body('Não foi possível localizar o aditivo vinculado para exportação.')
                                        ->danger()
                                        ->send();

                                    return null;
                                }

                                $unidade = $aditivo->obra?->unidade ?? 'sem-unidade';
                                $escopo = $aditivo->asEscopo?->escopo ?? 'sem-escopo';

                                $nomeArquivo = Str::of($unidade.' - '.$escopo)
                                    ->ascii()
                                    ->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-')
                                    ->replace('  ', ' ')
                                    ->trim()
                                    ->lower()
                                    ->append('.xlsx')
                                    ->toString();

                                return Excel::download(
                                    new ElaboracaoAditivoPlanilhaExport($aditivo->id),
                                    $nomeArquivo
                                );
                            }),
                    ])
                    ->schema([
                        Placeholder::make('tabela_aditivos')
                            ->hiddenLabel()
                            ->content(fn ($record) => new HtmlString(
                                view('filament.resources.asas.components.tabela-aditivo', [
                                    'record' => $record,
                                ])->render()
                            )),
                    ])
                    ->visible(fn ($record) => filled($record?->id))
                    ->columnSpan(3),

                Section::make('Prazo')
                    ->schema([
                        Group::make()
                            ->schema([
                                Select::make('altera_prazo')
                                    ->label('Há alteração de prazo?')
                                    ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                    ->options([
                                        'Não' => 'Não',
                                        'Sim' => 'Sim',
                                    ])
                                    ->required()
                                    ->partiallyRenderComponentsAfterStateUpdated(['dias_prazo'])
                                    ->live()
                                    ->native(false)
                                    ->afterStateUpdated(function (mixed $state, Livewire $livewire): void {
                                        if ($state === 'Não') {
                                            self::dispatchAutosave($livewire);
                                        }
                                    })
                                    ->columnSpan(function ($get) {

                                        if ($get('altera_prazo') === 'Sim') {
                                            return 1;
                                        }

                                        return 2;
                                    }),

                                self::autosaveOnBlur(
                                    TextInput::make('dias_prazo')
                                        ->label('Dias de prazo')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->numeric()
                                        ->suffix('dias')
                                        ->visible(fn ($get) => $get('altera_prazo') === 'Sim'),
                                ),
                            ])->columns(2),
                    ])->columnSpan(3),

                Section::make('Evidências')
                    ->schema([
                        Group::make()
                            ->schema([
                                self::autosaveFileUpload(
                                    FileUpload::make('foto_antes')
                                        ->label('Foto antes')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->multiple()
                                        ->panelLayout('grid')
                                        ->disk((string) config('filesystems.media_disk', 'r2'))
                                        ->directory(fn (?Asa $record) => self::asaDirectory($record, 'foto-antes'))
                                        ->downloadable()
                                        ->openable()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->maxSize(10240)
                                        ->hintIcon('heroicon-m-information-circle')
                                        ->hintIconTooltip('Formatos permitidos: JPG, JPEG, PNG, WEBP. Tamanho máximo: 10MB.'),
                                ),

                                self::autosaveFileUpload(
                                    FileUpload::make('foto_depois')
                                        ->label('Foto depois (caso já executado)')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->multiple()
                                        ->panelLayout('grid')
                                        ->disk((string) config('filesystems.media_disk', 'r2'))
                                        ->directory(fn (?Asa $record) => self::asaDirectory($record, 'foto-depois'))
                                        ->downloadable()
                                        ->openable()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->maxSize(10240)
                                        ->hintIcon('heroicon-m-information-circle')
                                        ->hintIconTooltip('Formatos permitidos: JPG, JPEG, PNG, WEBP. Tamanho máximo: 10MB.'),
                                ),

                                self::autosaveFileUpload(
                                    FileUpload::make('projeto_orcado')
                                        ->label('Projeto orçado')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->multiple()
                                        ->panelLayout('grid')
                                        ->disk((string) config('filesystems.media_disk', 'r2'))
                                        ->directory(fn (?Asa $record) => self::asaDirectory($record, 'projeto-orcado'))
                                        ->downloadable()
                                        ->openable()
                                        ->preserveFilenames()
                                        ->maxSize(716800)
                                        ->hintIcon('heroicon-m-information-circle')
                                        ->hintIconTooltip('Formatos permitidos: RVT, PDF e DWG. Tamanho máximo: 700MB.'),
                                ),

                                self::autosaveFileUpload(
                                    FileUpload::make('projeto_revisado')
                                        ->label('Projeto revisado')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->multiple()
                                        ->panelLayout('grid')
                                        ->disk((string) config('filesystems.media_disk', 'r2'))
                                        ->directory(fn (?Asa $record) => self::asaDirectory($record, 'projeto-revisado'))
                                        ->downloadable()
                                        ->openable()
                                        ->preserveFilenames()
                                        ->maxSize(716800)
                                        ->hintIcon('heroicon-m-information-circle')
                                        ->hintIconTooltip('Formatos permitidos: RVT, PDF e DWG. Tamanho máximo: 700MB.'),
                                ),

                                self::autosaveFileUpload(
                                    FileUpload::make('escopo_contratado')
                                        ->label('Escopo contratado')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->multiple()
                                        ->panelLayout('grid')
                                        ->disk((string) config('filesystems.media_disk', 'r2'))
                                        ->directory(fn (?Asa $record) => self::asaDirectory($record, 'escopo-contratado'))
                                        ->downloadable()
                                        ->openable()
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
                                ),

                                self::autosaveFileUpload(
                                    FileUpload::make('escopo_real')
                                        ->label('Escopo real')
                                        ->disabled(fn (?Asa $record) => self::shouldLockFields($record))
                                        ->multiple()
                                        ->panelLayout('grid')
                                        ->disk((string) config('filesystems.media_disk', 'r2'))
                                        ->directory(fn (?Asa $record) => self::asaDirectory($record, 'escopo-real'))
                                        ->downloadable()
                                        ->openable()
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
                                ),
                            ])->columns(2),
                    ])->columnSpan(3),
                /*
                Section::make('Itens')
                    ->schema([
                        Repeater::make('itens')
                            ->relationship()
                            ->label('Itens da ASA')
                            ->schema([
                                TextInput::make('item')
                                    ->label('Item')
                                    ->columnSpan(1),

                                TextInput::make('descricao')
                                    ->label('Descrição')
                                    ->rules(['required'])
                                    ->markAsRequired()
                                    ->columnSpan(4)
                                    ->validationMessages([
                                        'required' => 'Campo Obrigatório'
                                    ]),

                                TextInput::make('unidade')
                                    ->label('Unidade')
                                    ->columnSpan(1),

                                TextInput::make('quantidade')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->default(1)
                                    ->columnSpan(2),

                                TextInput::make('valor_unitario')
                                    ->label('Valor unitário')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0)
                                    ->columnSpan(2),

                                TextInput::make('valor_total')
                                    ->label('Valor total')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0)
                                    ->columnSpan(2),
                            ])
                            ->addActionLabel('Adicionar item'),
                    ])
                    ->columnSpan(2),
                */
            ])->columns(3);
    }
}
