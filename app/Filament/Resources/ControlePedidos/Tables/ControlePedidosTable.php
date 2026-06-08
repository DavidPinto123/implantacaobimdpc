<?php

namespace App\Filament\Resources\ControlePedidos\Tables;

use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Projeto;
use App\Models\User;
use Carbon\Carbon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ControlePedidosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /*
                |--------------------------------------------------------------------------
                | DADOS DO PROJETO
                |--------------------------------------------------------------------------
                */

                TextColumn::make('projeto.sigla')
                    ->label('SIGLA')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('projeto.nome')
                    ->label('UNIDADE')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('endereco_completo')
                    ->label('ENDEREÇO')
                    ->state(
                        fn ($record) => $record->projeto
                            ? "{$record->projeto->rua}, {$record->projeto->bairro} - {$record->projeto->cep}"
                            : '-'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('projeto.cidade.nome')
                    ->label('CIDADE')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('projeto.estado.nome')
                    ->label('UF')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('projeto.marca')
                    ->label('BANDEIRA')
                    ->toggleable(),

                TextColumn::make('projeto.pipeline')
                    ->label('PIPE')
                    ->toggleable(),

                TextColumn::make('fornecedor.nome')
                    ->label('CONSTRUTORA')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('projeto.area_academia')
                    ->label('ÁREA')
                    ->numeric()
                    ->toggleable(),

                TextColumn::make('situacao')
                    ->label('SITUAÇÃO')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'inaugurada' => 'Inaugurada',
                        'em_processo' => 'Em processo',
                        'em_obras' => 'Em Obra',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'inaugurada' => 'success',
                        'em_processo' => 'warning',
                        'em_obras' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('responsavelOrc.name')
                    ->label('RESPONSÁVEL - ORC')
                    ->toggleable(),

                TextColumn::make('projeto.inicio_obra')
                    ->label('I.O.')
                    ->date('d/m/Y')
                    ->toggleable(),

                TextColumn::make('projeto.entrega_obra')
                    ->label('E.O.')
                    ->date('d/m/Y')
                    ->toggleable(),

                TextColumn::make('projeto.inauguracao')
                    ->label('INAUGURAÇÃO')
                    ->date('d/m/Y')
                    ->toggleable(),

                TextColumn::make('dias_eo')
                    ->label('DIAS (E.O.)')
                    ->state(function ($record) {

                        if (! $record->projeto?->entrega_obra) {
                            return '-';
                        }

                        return (int) now()->startOfDay()
                            ->diffInDays(
                                Carbon::parse($record->projeto->entrega_obra)->startOfDay(),
                                false
                            );
                    })
                    ->formatStateUsing(
                        fn ($state) => $state === null ? '-' : "{$state} dias"
                    )
                    ->badge()
                    ->color(
                        fn ($state) => $state === '-'
                            ? 'gray'
                            : ($state > 0 ? 'danger' : 'success')
                    )
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('elaboracao_contrato')
                    ->label('Elaboração Contrato')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status CNPJ')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'definitivo' => 'CNPJ Definitivo',
                        'provisorio' => 'CNPJ Provisório',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'definitivo' => 'success',
                        'provisorio' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('contratacao')
                    ->label('Contratação')
                    ->date('d/m/Y')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('observacoes')
                    ->label('Observações')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valor_oi')
                    ->label('Valor OI')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valor_realizado')
                    ->label('Valor Realizado')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('realizado_nf')
                    ->label('Realizado NF')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('BRL')
                    ->badge()
                    ->color(fn ($state) => $state < 0 ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gestorObra.name')
                    ->label('Gestor Obra')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tamanho')
                    ->label('Tamanho')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('numero')
                    ->label('Número')
                    ->toggleable(isToggledHiddenByDefault: true),

                /*
                |--------------------------------------------------------------------------
                | PEDIDOS (JSON)
                |--------------------------------------------------------------------------
                */

                ...collect(ControlePedidoResource::pedidosMap())
                    ->map(function ($items, $groupName) {

                        $user = Auth::user();

                        $canEdit = $user?->hasAnyRole([
                            'coordenador_orcamento',
                            'super_admin',
                        ]);

                        return ColumnGroup::make(
                            $groupName,
                            collect($items)->map(function ($codigo) use ($canEdit) {

                                $codigoKey = str_replace('.', '_', $codigo);

                                if ($canEdit) {
                                    return ToggleColumn::make("pedidos.$codigoKey")
                                        ->label($codigo)
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->alignCenter()
                                        ->toggleable();
                                }

                                return IconColumn::make("pedidos.$codigoKey")
                                    ->label($codigo)
                                    ->boolean()
                                    ->alignCenter()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger');
                            })->toArray()
                        );
                    })
                    ->toArray(),

            ])

            /*
            |--------------------------------------------------------------------------
            | FILTROS
            |--------------------------------------------------------------------------
            */

            ->filters([

                SelectFilter::make('situacao')
                    ->label('Situação')
                    ->options([
                        'inaugurada' => 'Inaugurada',
                        'em_processo' => 'Em Processo',
                        'em_obras' => 'Em Obras',
                    ]),

                SelectFilter::make('status')
                    ->label('Status CNPJ')
                    ->options([
                        'definitivo' => 'CNPJ DEFINITIVO',
                        'provisorio' => 'CNPJ PROVISÓRIO',
                    ]),

                SelectFilter::make('cidade')
                    ->label('Cidade')
                    ->options(function () {
                        return Cidade::query()
                            ->whereHas('projetos.controlePedidos')
                            ->orderBy('nome')
                            ->pluck('nome', 'id')
                            ->toArray();
                    })
                    ->query(function ($query, $data) {
                        if (! $data['value']) {
                            return;
                        }

                        $query->whereHas('projeto', function ($q) use ($data) {
                            $q->where('cidade_id', $data['value']);
                        });
                    }),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(function () {
                        return Estado::query()
                            ->whereHas('projetos.controlePedidos')
                            ->orderBy('nome')
                            ->pluck('nome', 'id')
                            ->toArray();
                    })
                    ->query(function ($query, $data) {
                        if (! $data['value']) {
                            return;
                        }

                        $query->whereHas('projeto', function ($q) use ($data) {
                            $q->where('estado_id', $data['value']);
                        });
                    }),

                SelectFilter::make('responsavel_orc')
                    ->label('Responsável Orçamento')
                    ->options(function () {
                        return User::role('colaborador_orcamento')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function ($query, $data) {
                        if (! $data['value']) {
                            return;
                        }

                        $query->where('responsavel_orc', $data['value']);
                    }),

                SelectFilter::make('pipeline')
                    ->label('Pipeline')
                    ->options(
                        Projeto::query()
                            ->distinct()
                            ->pluck('pipeline', 'pipeline')
                            ->filter()
                            ->toArray()
                    )
                    ->query(function ($query, $data) {
                        if (! $data['value']) {
                            return;
                        }

                        $query->whereHas('projeto', function ($q) use ($data) {
                            $q->where('pipeline', $data['value']);
                        });
                    }),

            ])->filtersLayout(FiltersLayout::AboveContent)->deferFilters(false)

            ->striped()
            ->defaultSort('id', 'desc');
    }
}
