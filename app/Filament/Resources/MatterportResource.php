<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MatterportResource\Pages;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Matterport;
use App\Models\Pais;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class MatterportResource extends Resource
{
    protected static ?string $model = Matterport::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationLabel = 'Tour 360°';

    protected static ?string $modelLabel = 'Matterport';

    protected static ?int $navigationSort = 5;

    protected static string|null|UnitEnum $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Dashboard';

    protected static ?string $pluralModelLabel = 'Tour 360°';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identificação')
                    ->description('Dados básicos do tour')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('sigla')
                            ->label('Sigla')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nova_sigla')
                            ->label('Nova Sigla')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nome')
                            ->label('Nome')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Localização')
                    ->description('Endereço e região')
                    ->schema([
                        Select::make('pais_id')
                            ->label('País')
                            ->required()
                            ->relationship('pais', 'nome', function ($query) {
                                $query->orderBy('nome');
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('estado_id', null); // limpa estado quando muda o país
                                $set('cidade_id', null); // limpa cidade
                            }),

                        Select::make('estado_id')
                            ->label('Estado')
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->disabled(fn (callable $get) => ! $get('pais_id')) // desabilitado até selecionar um país
                            ->options(function (callable $get) {
                                $paisId = $get('pais_id');

                                return $paisId ? Estado::where('pais_id', $paisId)->orderBy('nome')->pluck('nome', 'id') : [];
                            })
                            ->afterStateUpdated(function (callable $set) {
                                $set('cidade_id', null); // limpa cidade quando muda o estado
                            }),

                        Select::make('cidade_id')
                            ->label('Cidade')
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->disabled(fn (callable $get) => ! $get('estado_id')) // desabilitado até selecionar um estado
                            ->options(function (callable $get) {
                                $estadoId = $get('estado_id');

                                return $estadoId ? Cidade::where('estado_id', $estadoId)->orderBy('nome')->pluck('nome', 'id') : [];
                            }),

                        Forms\Components\TextInput::make('endereco')
                            ->label('Endereço')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Links')
                    ->description('URLs relacionadas ao tour')
                    ->schema([
                        Forms\Components\TextInput::make('link_matterport1')
                            ->label('Link Matterport Principal'),

                        Forms\Components\TextInput::make('link_matterport2')
                            ->label('Link Matterport Secundário'),

                        Forms\Components\TextInput::make('link_matterport3')
                            ->label('Link Matterport Terciário'),

                        Forms\Components\TextInput::make('link_drone')
                            ->label('Link Drone'),

                        Forms\Components\TextInput::make('link_google_maps')
                            ->label('Link Google Maps'),
                    ])
                    ->columns(2),

                Section::make('Uploads')
                    ->description('Arquivos e imagens')
                    ->schema([
                        FileUpload::make('imagem')
                            ->label('Upload da capa da Matterport')
                            ->image()
                            ->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory('matterport')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('340')
                            ->imageEditorAspectRatios([
                                '16:9',
                            ]),

                        FileUpload::make('documentoPDF')
                            ->label('Documento PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('stores')
                            ->disk((string) config('filesystems.media_disk', 'r2'))
                            ->preserveFileNames()
                            ->downloadable()
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Primeira "coluna lógica": Nome, Cidade, Estado um ao lado do outro
                // Split::make([ // Usar Split para colocar os TextColumns lado a lado
                TextColumn::make('nome')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->extraAttributes(['class' => 'text-lg'])
                    ->aligncenter()
                    ->grow(false) // Impede que o texto ocupe todo o espaço disponível
                    ->limit(30), // Limita o comprimento do texto para reduzir espaçamento
                TextColumn::make('cidade_id')
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record->cidade?->nome)
                    ->grow(false)
                    ->aligncenter()
                    ->extraAttributes(['class' => 'text-lg'])
                    ->limit(20),
                TextColumn::make('estado_id')
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record->estado?->nome)
                    ->grow(false)
                    ->extraAttributes(['class' => 'text-lg'])
                    ->aligncenter()
                    ->limit(20),
                // ]),

                // Segunda "coluna lógica": Imagem da Matterport (em um Stack para consistência, embora não estritamente necessário para um único item)
                Stack::make([
                    ImageColumn::make('imagem')
                        ->url(fn ($record) => $record->link_matterport1)
                        ->openUrlInNewTab()
                        // ->imageResizeTargetWidth(400)
                        ->extraImgAttributes([
                            'style' => 'border-radius: 8px; width: 100%;
                                       height: 100%;
                                       object-fit: cover;',
                        ])
                        ->disk((string) config('filesystems.media_disk', 'r2'))
                        ->aligncenter(),
                ]),

                // Terceira "coluna lógica": Links (em um Stack para empilhá-los verticalmente)
                Stack::make([
                    TextColumn::make('link_matterport1')
                        ->label('Link Matterport 1')
                        ->extraAttributes(['class' => 'text-base'])
                        ->formatStateUsing(fn ($state) => 'Link para o primeiro Tour 360º')
                        ->url(fn ($record) => $record->link_matterport1)
                        ->openUrlInNewTab()
                        ->aligncenter(),
                    TextColumn::make('link_matterport2')
                        ->label('Link Matterport 2')
                        ->extraAttributes(['class' => 'text-base'])
                        ->formatStateUsing(fn ($state) => 'Link para o segundo Tour 360º')
                        ->url(fn ($record) => $record->link_matterport2)
                        ->openUrlInNewTab()
                        ->aligncenter(),
                    TextColumn::make('link_matterport3')
                        ->label('Link Matterport 3')
                        ->extraAttributes(['class' => 'text-base'])
                        ->formatStateUsing(fn ($state) => 'Link para o terceiro Tour 360º')
                        ->url(fn ($record) => $record->link_matterport3)
                        ->openUrlInNewTab()
                        ->aligncenter(),
                    TextColumn::make('link_drone')
                        ->label('Url Drone')
                        ->extraAttributes(['class' => 'text-base'])
                        ->formatStateUsing(fn ($state) => 'Link para o vídeo de Drone')
                        ->url(fn ($record) => $record->link_drone)
                        ->openUrlInNewTab(true)
                        ->aligncenter(),
                    TextColumn::make('link_google_maps')
                        ->label('Url Google Maps')
                        ->extraAttributes(['class' => 'text-base'])
                        ->formatStateUsing(fn ($state) => 'Link para o Google Maps')
                        ->url(fn ($record) => $record->link_google_maps)
                        ->openUrlInNewTab(true)
                        ->aligncenter(),
                    TextColumn::make('documentoPDF')
                        ->label('Visualizar Documento PDF')
                        ->state(fn ($record) => $record->documentoPDF ? 'Link para o Relatório Fotográfico' : null)
                        ->url(function ($record): ?string {
                            if (! $record->documentoPDF) {
                                return null;
                            }

                            /** @var FilesystemAdapter $disk */
                            $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                            return $disk->url($record->documentoPDF);
                        })
                        ->openUrlInNewTab(true)
                        ->extraAttributes(['class' => 'text-base'])
                        // ->icon('heroicon-o-document-arrow-down') // Ícone de visualização
                        // ->color('primary')
                        ->aligncenter()
                        ->limit(40),
                ]),
            ])->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn () => null)
            ->contentGrid([
                'md' => 2, // Em telas médias, 2 colunas
                'xl' => 3, // Em telas extra-grandes, 3 colunas
            ])
            ->filters([
                Filter::make('localizacao')
                    ->form([
                        Grid::make(3)
                            ->schema([
                                Select::make('pais_id')
                                    ->label('País')
                                    ->options(Pais::orderBy('nome')->pluck('nome', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->default(null)
                                    ->afterStateUpdated(fn (callable $set) => $set('estado_id', null)),

                                Select::make('estado_id')
                                    ->label('Estado')
                                    ->options(
                                        fn ($get) => Estado::where('pais_id', $get('pais_id'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->default(null)
                                    ->afterStateUpdated(fn (callable $set) => $set('cidade_id', null)),

                                Select::make('cidade_id')
                                    ->label('Cidade')
                                    ->options(
                                        fn ($get) => Cidade::where('estado_id', $get('estado_id'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(null),

                                Select::make('codigo')
                                    ->label('Código')
                                    ->options(Matterport::orderBy('codigo')->pluck('codigo', 'codigo'))
                                    ->searchable()
                                    ->preload()
                                    ->default(null),

                                Select::make('sigla')
                                    ->label('Sigla')
                                    ->options(Matterport::whereNotNull('sigla')->orderBy('sigla')->pluck('sigla', 'sigla'))
                                    ->searchable()
                                    ->preload()
                                    ->default(null),

                                Select::make('nova_sigla')
                                    ->label('Nova Sigla')
                                    ->options(Matterport::whereNotNull('nova_sigla')->orderBy('nova_sigla')->pluck('nova_sigla', 'nova_sigla'))
                                    ->searchable()
                                    ->preload()
                                    ->default(null),
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['pais_id'], fn ($q, $pais) => $q->where('pais_id', $pais))
                            ->when($data['estado_id'], fn ($q, $estado) => $q->where('estado_id', $estado))
                            ->when($data['cidade_id'], fn ($q, $cidade) => $q->where('cidade_id', $cidade))
                            ->when($data['codigo'], fn ($q, $codigo) => $q->where('codigo', $codigo))
                            ->when($data['sigla'], fn ($q, $sigla) => $q->where('sigla', $sigla))
                            ->when($data['nova_sigla'], fn ($q, $nova_sigla) => $q->where('nova_sigla', $nova_sigla));
                    })
                    ->indicateUsing(fn (array $data): array => array_filter([
                        $data['pais_id'] ? 'País: '.(Pais::find($data['pais_id'])?->nome ?? '') : null,
                        $data['estado_id'] ? 'Estado: '.(Estado::find($data['estado_id'])?->nome ?? '') : null,
                        $data['cidade_id'] ? 'Cidade: '.(Cidade::find($data['cidade_id'])?->nome ?? '') : null,
                        $data['codigo'] ? 'Código: '.$data['codigo'] : null,
                        $data['sigla'] ? 'Sigla: '.$data['sigla'] : null,
                        $data['nova_sigla'] ? 'Nova Sigla: '.$data['nova_sigla'] : null,
                    ])),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->actions([
                // Tables\Actions\ViewAction::make(),
                EditAction::make(),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMatterports::route('/'),
            'create' => Pages\CreateMatterport::route('/create'),
            'view' => Pages\ViewMatterport::route('/{record}'),
            'edit' => Pages\EditMatterport::route('/{record}/edit'),
        ];
    }
}
