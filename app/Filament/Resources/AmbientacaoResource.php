<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmbientacaoResource\Pages;
use App\Models\Ambientacao;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Pais;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AmbientacaoResource extends Resource
{
    protected static ?string $model = Ambientacao::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Ambientação';

    protected static ?string $modelLabel = 'Ambientação';

    protected static ?string $slug = 'ambientacoes';

    protected static ?int $navigationSort = 6;

    protected static string|null|UnitEnum $navigationGroup = null;

    protected static ?string $pluralModelLabel = 'Ambientação';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identificação')
                    ->description('Dados básicos da unidade/obra')
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
                    ->description('Região da unidade/obra')
                    ->schema([
                        Select::make('pais_id')
                            ->label('País')
                            ->relationship('pais', 'nome', function ($query) {
                                $query->orderBy('nome');
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('estado_id', null);
                                $set('cidade_id', null);
                            }),

                        Select::make('estado_id')
                            ->label('Estado')
                            ->searchable()
                            ->reactive()
                            ->disabled(fn (callable $get) => ! $get('pais_id'))
                            ->options(function (callable $get) {
                                $paisId = $get('pais_id');

                                return $paisId ? Estado::where('pais_id', $paisId)->orderBy('nome')->pluck('nome', 'id') : [];
                            })
                            ->afterStateUpdated(function (callable $set) {
                                $set('cidade_id', null);
                            }),

                        Select::make('cidade_id')
                            ->label('Cidade')
                            ->searchable()
                            ->reactive()
                            ->disabled(fn (callable $get) => ! $get('estado_id'))
                            ->options(function (callable $get) {
                                $estadoId = $get('estado_id');

                                return $estadoId ? Cidade::where('estado_id', $estadoId)->orderBy('nome')->pluck('nome', 'id') : [];
                            }),
                    ])
                    ->columns(2),

                Section::make('Ambiente')
                    ->description('Pavimento e ambiente representados pelo render')
                    ->schema([
                        Forms\Components\TextInput::make('pavimento')
                            ->label('Pavimento')
                            ->placeholder('Ex: Térreo, 1º Pavimento')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('ambiente')
                            ->label('Ambiente')
                            ->placeholder('Ex: Recepção, Sala de Musculação')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Render 360°')
                    ->description('Link da página de renderização 360° gerada na Autodesk (ex: pano.autodesk.com/...)')
                    ->schema([
                        Forms\Components\TextInput::make('link_render')
                            ->label('Link do Render 360°')
                            ->url()
                            ->required()
                            ->placeholder('https://pano.autodesk.com/pano.html?...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ambiente')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->extraAttributes(['class' => 'text-lg'])
                    ->aligncenter()
                    ->grow(false)
                    ->limit(30),
                TextColumn::make('pavimento')
                    ->searchable()
                    ->grow(false)
                    ->extraAttributes(['class' => 'text-lg'])
                    ->aligncenter()
                    ->limit(20),
                TextColumn::make('nome')
                    ->searchable()
                    ->label('Unidade')
                    ->grow(false)
                    ->extraAttributes(['class' => 'text-lg'])
                    ->aligncenter()
                    ->limit(20),

                Stack::make([
                    TextColumn::make('codigo')
                        ->label('Código')
                        ->extraAttributes(['class' => 'text-base'])
                        ->aligncenter(),
                    TextColumn::make('cidade_id')
                        ->label('Cidade')
                        ->getStateUsing(fn ($record) => $record->cidade?->nome)
                        ->extraAttributes(['class' => 'text-base'])
                        ->aligncenter(),
                    TextColumn::make('estado_id')
                        ->label('Estado')
                        ->getStateUsing(fn ($record) => $record->estado?->nome)
                        ->extraAttributes(['class' => 'text-base'])
                        ->aligncenter(),
                ]),

                TextColumn::make('link_render')
                    ->label('Render 360°')
                    ->extraAttributes(['class' => 'text-base'])
                    ->formatStateUsing(fn ($state) => 'Abrir Render 360º')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->weight(FontWeight::Bold)
                    ->url(fn ($record) => $record->link_render)
                    ->openUrlInNewTab()
                    ->aligncenter(),
            ])->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn () => null)
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
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
                                    ->options(Ambientacao::orderBy('codigo')->pluck('codigo', 'codigo'))
                                    ->searchable()
                                    ->preload()
                                    ->default(null),

                                Select::make('sigla')
                                    ->label('Sigla')
                                    ->options(Ambientacao::whereNotNull('sigla')->orderBy('sigla')->pluck('sigla', 'sigla'))
                                    ->searchable()
                                    ->preload()
                                    ->default(null),

                                Select::make('nova_sigla')
                                    ->label('Nova Sigla')
                                    ->options(Ambientacao::whereNotNull('nova_sigla')->orderBy('nova_sigla')->pluck('nova_sigla', 'nova_sigla'))
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

                SelectFilter::make('pavimento')
                    ->label('Pavimento')
                    ->options(fn () => Ambientacao::whereNotNull('pavimento')->orderBy('pavimento')->pluck('pavimento', 'pavimento')->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->actions([
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
            'index' => Pages\ListAmbientacaos::route('/'),
            'create' => Pages\CreateAmbientacao::route('/create'),
            'view' => Pages\ViewAmbientacao::route('/{record}'),
            'edit' => Pages\EditAmbientacao::route('/{record}/edit'),
        ];
    }
}
