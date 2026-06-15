<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegiaoInteresseResource\Pages;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Resources\RegiaoInteresseResource\RelationManagers;
use App\Filament\Resources\Get;
use App\Models\RegiaoInteresse;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Pais;
use App\Models\Estado;
use App\Models\Cidade;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Grid;
use Filament\Tables\Enums\ActionsPosition;


class RegiaoInteresseResource extends Resource
{
    
    protected static ?string $model = RegiaoInteresse::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Cadastro de Regiões de Interesse';
  
  	protected static ?string $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $modelLabel = 'Região de Interesse';

    protected static ?string $slug = 'regioes-de-interesse';

    protected static ?string $breadcrumb = 'Regiões de Interesse';

    protected static ?string $pluralModelLabel = 'Lista de Regiões de Interesse'; 
  
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('endereco')
                    ->label('Endereço')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('pais_id')
                    ->label('País')
                    ->relationship('pais', 'nome')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('estado_id')
                    ->label('Estado')
                    ->options(function (callable $get) {
                        $paisId = $get('pais_id');
                        if (!$paisId) {
                            return [];
                        }
                        return \App\Models\Estado::where('pais_id', $paisId)
                            ->orderBy('nome')
                            ->pluck('nome', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('cidade_id', null)),
                Forms\Components\Select::make('cidade_id')
                    ->label('Cidade')
                    ->options(function (callable $get) {
                        $estadoId = $get('estado_id');
                        if (!$estadoId) {
                            return [];
                        }
                        return \App\Models\Cidade::where('estado_id', $estadoId)
                            ->orderBy('nome')
                            ->pluck('nome', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('bairro')
                    ->label('Bairro')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('endereco')
                    ->label('Endereço')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bairro')
                    ->searchable(),
				Tables\Columns\TextColumn::make('cidade.nome')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado.nome')
                    ->sortable(),
				Tables\Columns\TextColumn::make('pais.nome')
                    ->label('País')
                    ->sortable(),        
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('localizacao')
                    ->form([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('pais_id')
                                    ->label('País')
                                    ->options(Pais::orderBy('nome')->pluck('nome', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->default(null)
                                    ->afterStateUpdated(fn (callable $set) => $set('estado_id', null)),

                                Forms\Components\Select::make('estado_id')
                                    ->label('Estado')
                                    ->options(fn ($get) =>
                                        Estado::where('pais_id', $get('pais_id'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->default(null)
                                    ->afterStateUpdated(fn (callable $set) => $set('cidade_id', null)),

                                Forms\Components\Select::make('cidade_id')
                                    ->label('Cidade')
                                    ->options(fn ($get) =>
                                        Cidade::where('estado_id', $get('estado_id'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(null),
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['pais_id'], fn ($q, $pais) => $q->where('pais_id', $pais))
                            ->when($data['estado_id'], fn ($q, $estado) => $q->where('estado_id', $estado))
                            ->when($data['cidade_id'], fn ($q, $cidade) => $q->where('cidade_id', $cidade));
                    })
                    ->indicateUsing(fn (array $data): array => array_filter([
                        $data['pais_id'] ? 'País: ' . (Pais::find($data['pais_id'])?->nome ?? '') : null,
                        $data['estado_id'] ? 'Estado: ' . (Estado::find($data['estado_id'])?->nome ?? '') : null,
                        $data['cidade_id'] ? 'Cidade: ' . (Cidade::find($data['cidade_id'])?->nome ?? '') : null,
                    ])),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->actions([
                Tables\Actions\ViewAction::make()
              		->label(''),
                Tables\Actions\EditAction::make()
              		->label(''),
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                  		->label('Excluir selecionado(s)'),
                ]),
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
            'index' => Pages\ListRegiaoInteresses::route('/'),
            'create' => Pages\CreateRegiaoInteresse::route('/create'),
            'view' => Pages\ViewRegiaoInteresse::route('/{record}'),
            'edit' => Pages\EditRegiaoInteresse::route('/{record}/edit'),
        ];
    }
}
