<?php

namespace App\Filament\Resources\GrupoOis\Tables;

use App\Models\GrupoOi;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GrupoOisTable
{
    private static function idsEmOrdemDeArvore(): array
    {
        $todos = GrupoOi::query()
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get(['id', 'parent_id']);

        $porPai = $todos->groupBy(fn (GrupoOi $g): string => (string) ($g->parent_id ?? ''));

        $ordenados = [];
        $percorrer = function (?int $paiId) use (&$percorrer, $porPai, &$ordenados): void {
            $chave = (string) ($paiId ?? '');
            foreach ($porPai->get($chave, collect()) as $grupo) {
                $ordenados[] = $grupo->id;
                $percorrer($grupo->id);
            }
        };

        $percorrer(null);

        return $ordenados;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $ids = self::idsEmOrdemDeArvore();

                if ($ids === []) {
                    return $query;
                }

                $lista = implode(',', $ids);

                return $query->orderByRaw("FIELD(grupo_ois.id, {$lista})");
            })
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (GrupoOi $record): string => str_repeat('— ', max(0, $record->nivel - 1)).$record->nome),

                TextColumn::make('parent.nome')
                    ->label('Grupo pai')
                    ->placeholder('— (raiz)')
                    ->toggleable(),

                TextColumn::make('nivel')
                    ->label('Nível')
                    ->badge()
                    ->sortable(),

                TextColumn::make('ordem')
                    ->label('Ordem')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('children_count')
                    ->label('Sub-grupos')
                    ->counts('children')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('nivel')
                    ->label('Nível')
                    ->options(fn (): array => GrupoOi::query()
                        ->distinct()
                        ->orderBy('nivel')
                        ->pluck('nivel', 'nivel')
                        ->all()),

                TernaryFilter::make('parent_id')
                    ->label('Apenas raízes')
                    ->placeholder('Todos')
                    ->trueLabel('Somente raízes')
                    ->falseLabel('Somente sub-grupos')
                    ->queries(
                        true: fn ($query) => $query->whereNull('parent_id'),
                        false: fn ($query) => $query->whereNotNull('parent_id'),
                    ),

                TernaryFilter::make('is_active')
                    ->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultPaginationPageOption(50);
    }
}
