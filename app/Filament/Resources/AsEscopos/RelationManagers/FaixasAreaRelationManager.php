<?php

namespace App\Filament\Resources\AsEscopos\RelationManagers;

use App\Models\AsFaixaArea;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FaixasAreaRelationManager extends RelationManager
{
    protected static string $relationship = 'faixasArea';

    protected static ?string $title = 'Faixas por Área';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('valor_m2')
                    ->label('Valor por m²')
                    ->numeric()
                    ->required()
                    ->minValue(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->columns([
                TextColumn::make('nome')
                    ->label('Faixa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('area_min')
                    ->label('Área mínima')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.')),

                TextColumn::make('area_max')
                    ->label('Área máxima')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state !== null
                        ? number_format((float) $state, 2, ',', '.')
                        : 'Sem limite'
                    ),

                TextColumn::make('valor_m2')
                    ->label('Valor/m²')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'R$ '.number_format((float) $state, 2, ',', '.')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Vincular faixa')
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Faixa de Área')
                            ->searchable()
                            ->preload()
                            ->options(
                                AsFaixaArea::query()
                                    ->orderBy('area_min')
                                    ->pluck('nome', 'id')
                                    ->toArray()
                            ),

                        TextInput::make('valor_m2')
                            ->label('Valor por m²')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        TextInput::make('valor_m2')
                            ->label('Valor por m²')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ]),

                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
