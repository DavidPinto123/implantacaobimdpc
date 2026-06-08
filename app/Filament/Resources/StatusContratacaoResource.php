<?php

namespace App\Filament\Resources;

use App\Models\Status;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StatusContratacaoResource extends Resource
{
    protected static ?string $model = Status::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Status';

    protected static ?string $label = 'Status';

    protected static ?string $pluralLabel = 'Status';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('contexto')
                    ->label('Contexto')
                    ->options([
                        'retrofit' => 'Controle de Pedidos — Retrofit',
                        'entrega_contratual_status' => 'Entrega Contratual — Status',
                        'entrega_contratual_previsto' => 'Entrega Contratual — Previsto em contrato?',
                    ])
                    ->required()
                    ->disabled(fn (?Status $record): bool => $record?->is_protected ?? false)
                    ->live(),
                TextInput::make('nome')
                    ->label('Nome do Status')
                    ->required()
                    ->disabled(fn (?Status $record): bool => $record?->is_protected ?? false),
                TextInput::make('slug')
                    ->label('Slug (identificador interno)')
                    ->required()
                    ->disabled(fn (?Status $record): bool => $record?->is_protected ?? false)
                    ->helperText('Use letras minúsculas, números e underscores. Não pode mudar em status protegidos.'),
                ColorPicker::make('cor')
                    ->label('Cor')
                    ->required(),
                TextInput::make('ordem')
                    ->label('Ordem de Exibição')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
                Select::make('tipo_custo')
                    ->label('Tipo de custo')
                    ->options([
                        'contrato' => 'Habilita "Custo c/ contrato"',
                        'sem_contrato' => 'Habilita "Custo s/ contrato"',
                        'nenhum' => 'Não habilita nenhum custo',
                    ])
                    ->placeholder('—')
                    ->helperText('Só se aplica ao contexto "Entrega Contratual — Previsto em contrato?".')
                    ->visible(fn (callable $get): bool => $get('contexto') === 'entrega_contratual_previsto'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contexto')
                    ->label('Contexto')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nome')
                    ->label('Status')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(),
                Tables\Columns\ColorColumn::make('cor')
                    ->label('Cor'),
                Tables\Columns\TextColumn::make('ordem')
                    ->label('Ordem')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_custo')
                    ->label('Tipo custo')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_protected')
                    ->label('Protegido')
                    ->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo'),
            ])
            ->filters([
                SelectFilter::make('contexto')
                    ->options([
                        'retrofit' => 'Retrofit',
                        'entrega_contratual_status' => 'Entrega Contratual — Status',
                        'entrega_contratual_previsto' => 'Entrega Contratual — Previsto',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Status $record): bool => ! $record->is_protected),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($action, $records): void {
                            foreach ($records as $record) {
                                if ($record->is_protected) {
                                    $action->cancel();

                                    return;
                                }
                            }
                        }),
                ]),
            ]);
    }
}
