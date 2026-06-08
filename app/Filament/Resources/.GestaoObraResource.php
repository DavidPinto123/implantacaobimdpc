<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GestaoObraResource\Pages;
use App\Filament\Resources\GestaoObraResource\RelationManagers;
use App\Models\GestaoObra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GestaoObraResource extends Resource
{
    protected static ?string $model = GestaoObra::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
  
    protected static ?string $navigationLabel = 'Gestão de Obras';
  
    protected static ?string $navigationGroup = 'Fornecedor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('codigo')
              		->label('Cód. Obra')
              		->required()
                    ->validationMessages([
                        'required' => 'O código da obra é obrigatório.',
                        'unique' => 'Este código já está cadastrado.',
                        'max' => 'O código não pode ter mais que 20 caracteres.',
                    ]),
                Forms\Components\TextInput::make('nome')
              		->label('Nome Obra')
              		->required()
    				->maxLength(255)
                    ->validationMessages([
                        'required' => 'O nome da obra é obrigatório.',
                        'max' => 'O nome não pode ter mais que 255 caracteres.',
                    ]),
                Forms\Components\Select::make('construtora_id')
                    ->relationship('construtora', 'nome')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->validationMessages([
                        'required' => 'Selecione uma fornecedor.',
                    ]),

                Forms\Components\TextInput::make('orcamento_inicial')
              		->label('OI')
                    ->numeric()
                    ->required()
                    ->prefix('R$')
                    ->mask(fn () => 'money')
                    ->validationMessages([
                        'required' => 'Informe o orçamento inicial.',
                        'numeric' => 'O valor deve ser numérico.',
                    ]),
                Forms\Components\TextInput::make('realizado')
              		->label('Realizado')
                    ->numeric()
                    ->required()
                    ->prefix('R$')
                    ->mask(fn () => 'money')
                    ->validationMessages([
                        'required' => 'Informe o valor realizado.',
                        'numeric' => 'O valor deve ser numérico.',
                    ]),

                Forms\Components\TextInput::make('comprometido')
                    ->label('Comprometido')
                    ->numeric()
                    ->required()
                    ->prefix('R$')
                    ->mask(fn () => 'money')
                    ->validationMessages([
                        'required' => 'Informe o valor comprometido.',
                        'numeric' => 'O valor deve ser numérico.',
                    ]),

                Forms\Components\TextInput::make('pdp')
                    ->label('PDP')
                    ->numeric()
                    ->required()
                    ->prefix('R$')
                    ->mask(fn () => 'money')
                    ->validationMessages([
                        'required' => 'Informe o valor do PDP.',
                        'numeric' => 'O valor deve ser numérico.',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')->label('Cód. Obra')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('nome')->label('Nome Obra')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('fornecedor.nome')->label('Fornecedor')->sortable()->searchable(),

                Tables\Columns\TextColumn::make('orcamento_inicial')->label('OI')->money('BRL'),
                Tables\Columns\TextColumn::make('realizado')->money('BRL'),
                Tables\Columns\TextColumn::make('comprometido')->money('BRL'),
                Tables\Columns\TextColumn::make('pdp')->label('PDP')->money('BRL'),

                Tables\Columns\TextColumn::make('saldo')->money('BRL')->label('Saldo'),
            ])
            ->defaultSort('codigo')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListGestaoObras::route('/'),
            'create' => Pages\CreateGestaoObra::route('/create'),
            'edit' => Pages\EditGestaoObra::route('/{record}/edit'),
        ];
    }
}
