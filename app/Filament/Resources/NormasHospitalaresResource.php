<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NormasHospitalaresResource\Pages;
use App\Models\AmbienteRdc50;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use UnitEnum;

class NormasHospitalaresResource extends Resource
{
    protected static ?string $model = AmbienteRdc50::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Normas Hospitalares';

    protected static ?string $modelLabel = 'Ambiente da RDC50';

    protected static ?string $pluralModelLabel = 'Normas Hospitalares';

    protected static ?string $slug = 'normas-hospitalares';

    protected static string|null|UnitEnum $navigationGroup = null;

    private const CIRCULACAO_OPTIONS = [
        'Pública' => 'Pública',
        'Interna' => 'Interna',
        'Industrial' => 'Industrial',
        'Séptica' => 'Séptica',
        'Asséptica' => 'Asséptica',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ambiente')
                    ->label('Ambiente')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->extraHeaderAttributes(['style' => 'width: 26%; min-width: 260px']),
                TextInputColumn::make('nome_fiorentini')
                    ->label('Nome Fiorentini')
                    ->placeholder('Preencher pelo projetista')
                    ->searchable()
                    ->sortable()
                    ->extraHeaderAttributes(['style' => 'width: 20%; min-width: 200px']),
                TextColumn::make('unidade_funcional')
                    ->label('Unidade Funcional')
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->extraHeaderAttributes(['style' => 'width: 15%; min-width: 150px']),
                TextColumn::make('subgrupo')
                    ->label('Subgrupo')
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->extraHeaderAttributes(['style' => 'width: 15%; min-width: 150px']),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Ambiente-fim' ? 'success' : 'gray'),
                SelectColumn::make('circulacao')
                    ->label('Circulação')
                    ->options(self::CIRCULACAO_OPTIONS)
                    ->placeholder('Selecionar'),
                TextColumn::make('num_atividade')
                    ->label('Nº Ativ.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('obrigatoriedade')
                    ->label('Obrigatoriedade')
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('quantificacao_minima')
                    ->label('Quantificação (mín.)')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('pe_direito_minimo')
                    ->label('Pé Direito Mínimo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('area_dimensao_minima')
                    ->label('Área/Dimensão Mínima')
                    ->searchable()
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(),
                TextColumn::make('instalacoes')
                    ->label('Instalações')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('rev_piso')
                    ->label('Rev. Piso')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rev_parede')
                    ->label('Rev. Parede')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rev_forro')
                    ->label('Rev. Forro')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rev_rodape')
                    ->label('Rev. Rodapé')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rev_rodameio')
                    ->label('Rev. Rodameio')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextInputColumn::make('comentarios')
                    ->label('Comentários')
                    ->placeholder('Adicionar comentário')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('unidade_funcional')
                    ->searchable()
                    ->preload()
                    ->label('Unidade Funcional')
                    ->options(
                        AmbienteRdc50::query()
                            ->distinct()
                            ->orderBy('unidade_funcional')
                            ->pluck('unidade_funcional', 'unidade_funcional')),
                SelectFilter::make('subgrupo')
                    ->searchable()
                    ->preload()
                    ->label('Subgrupo')
                    ->options(
                        AmbienteRdc50::query()
                            ->distinct()
                            ->orderBy('subgrupo')
                            ->pluck('subgrupo', 'subgrupo')),
                SelectFilter::make('tipo')
                    ->searchable()
                    ->preload()
                    ->label('Tipo')
                    ->options(
                        AmbienteRdc50::query()
                            ->distinct()
                            ->orderBy('tipo')
                            ->pluck('tipo', 'tipo')),
                SelectFilter::make('obrigatoriedade')
                    ->searchable()
                    ->preload()
                    ->label('Obrigatoriedade')
                    ->options(
                        AmbienteRdc50::query()
                            ->distinct()
                            ->orderBy('obrigatoriedade')
                            ->pluck('obrigatoriedade', 'obrigatoriedade')),
                SelectFilter::make('circulacao')
                    ->searchable()
                    ->preload()
                    ->label('Circulação')
                    ->options(self::CIRCULACAO_OPTIONS),
            ], layout: FiltersLayout::Dropdown)
            ->filtersFormColumns(2)
            ->filtersFormWidth('lg')
            ->groups([
                Group::make('unidade_funcional')->label('Unidade Funcional'),
                Group::make('subgrupo')->label('Subgrupo'),
                Group::make('tipo')->label('Tipo'),
                Group::make('obrigatoriedade')->label('Obrigatoriedade'),
                Group::make('circulacao')->label('Circulação'),
            ])
            ->defaultSort('id')
            ->actions([
                //
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListNormasHospitalares::route('/'),
        ];
    }
}
