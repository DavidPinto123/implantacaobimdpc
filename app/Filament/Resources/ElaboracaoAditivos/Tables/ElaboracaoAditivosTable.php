<?php

namespace App\Filament\Resources\ElaboracaoAditivos\Tables;

use App\Models\Construtora;
use App\Models\Obras;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ElaboracaoAditivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obra_id')
                    ->label('Obra')
                    ->formatStateUsing(fn ($state) => Obras::find($state)?->unidade ?? '-')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('asEscopo.escopo')
                    ->label('Escopo')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(40),

                TextColumn::make('construtora_id')
                    ->label('Fornecedor')
                    ->formatStateUsing(fn ($state) => Construtora::find($state)?->nome ?? '-')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('itens')
                    ->label('Itens')
                    ->getStateUsing(function ($record): array {
                        if (! $record->relationLoaded('itens')) {
                            $record->load('itens');
                        }

                        return $record->itens
                            ->map(fn ($item) => ($item->item ?? '-').' - '.($item->descricao_servico ?? '-'))
                            ->toArray();
                    })
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->wrap(),

                TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                /*
                SelectFilter::make('construtora_id')
                    ->label('Fornecedor')
                    ->options(function () {
                        $user = Auth::user();

                        if (! $user) {
                            return [];
                        }

                        $podeVerTudo =
                            $user->hasRole('super_admin') ||
                            $user->hasRole('Coordenador') ||
                            $user->setores()->where('setor', 'Obras')->exists();

                        if ($podeVerTudo) {
                            return Construtora::query()
                                ->orderBy('nome')
                                ->pluck('nome', 'id')
                                ->toArray();
                        }

                        if ($user->hasRole('Fornecedor') && $user->construtoras_id) {
                            return Construtora::query()
                                ->where('id', $user->construtoras_id)
                                ->orderBy('nome')
                                ->pluck('nome', 'id')
                                ->toArray();
                        }

                        return [];
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('gestor_id')
                    ->label('Gestor')
                    ->options(function () {
                        $user = Auth::user();

                        if (! $user) {
                            return [];
                        }

                        $podeVerTudo =
                            $user->hasRole('super_admin') ||
                            $user->hasRole('Coordenador') ||
                            $user->setores()->where('setor', 'Obras')->exists();

                        if ($podeVerTudo) {
                            return User::query()
                                ->get()
                                ->filter(fn (User $item) => $item->hasRole('Colaborador'))
                                ->filter(fn (User $item) => $item->setores()->where('setor', 'Obras')->exists())
                                ->pluck('name', 'id')
                                ->toArray();
                        }

                        if ($user->hasRole('Fornecedor')) {
                            return User::query()
                                ->where('construtoras_id', $user->construtoras_id)
                                ->get()
                                ->filter(fn (User $item) => $item->hasRole('Colaborador'))
                                ->filter(fn (User $item) => $item->setores()->where('setor', 'Obras')->exists())
                                ->pluck('name', 'id')
                                ->toArray();
                        }

                        return [];
                    })
                    ->searchable()
                    ->preload(),
                    */
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
