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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
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
                    ->description('Localização e ambiente representados pelo render')
                    ->schema([
                        Forms\Components\TextInput::make('bloco_torre')
                            ->label('Bloco/Torre')
                            ->placeholder('Ex: Torre A, Bloco 1')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pavimento')
                            ->label('Pavimento')
                            ->placeholder('Ex: Térreo, 1º Pavimento')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('departamento')
                            ->label('Departamento')
                            ->placeholder('Ex: Musculação, Vestiário')
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
                    TextColumn::make('bloco_torre')
                        ->label('Bloco/Torre')
                        ->extraAttributes(['class' => 'text-base'])
                        ->aligncenter(),
                    TextColumn::make('departamento')
                        ->label('Departamento')
                        ->extraAttributes(['class' => 'text-base'])
                        ->aligncenter(),
                    TextColumn::make('codigo')
                        ->label('Código')
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
                SelectFilter::make('bloco_torre')
                    ->label('Bloco/Torre')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Ambientacao::whereNotNull('bloco_torre')->orderBy('bloco_torre')->pluck('bloco_torre', 'bloco_torre')->toArray()),

                SelectFilter::make('pavimento')
                    ->label('Pavimento')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Ambientacao::whereNotNull('pavimento')->orderBy('pavimento')->pluck('pavimento', 'pavimento')->toArray()),

                SelectFilter::make('departamento')
                    ->label('Departamento')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Ambientacao::whereNotNull('departamento')->orderBy('departamento')->pluck('departamento', 'departamento')->toArray()),

                SelectFilter::make('ambiente')
                    ->label('Ambiente')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Ambientacao::whereNotNull('ambiente')->orderBy('ambiente')->pluck('ambiente', 'ambiente')->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
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
