<?php

namespace App\Filament\Pages;

use App\Models\ControlePedido;
use App\Tables\Columns\ProgressColumn;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class DashboardPedidos extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Controle de Pedidos - %';

    protected static ?string $title = 'Controle de Pedidos - %';

    protected static ?int $navigationSort = 1;

    protected static string|null|UnitEnum $navigationGroup = 'Orçamentos';

    protected string $view = 'filament.pages.dashboard-pedidos';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ControlePedido::query()
                    ->with(['projeto', 'construtora', 'responsavelOrc', 'gestorObra'])
            )
            ->columns([

                Tables\Columns\TextColumn::make('projeto.sigla')
                    ->label('SIGLA')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.nome')
                    ->label('UNIDADE')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('endereco_completo')
                    ->label('ENDEREÇO')
                    ->state(
                        fn ($record) => $record->projeto
                            ? "{$record->projeto->rua}, {$record->projeto->bairro} - {$record->projeto->cep}"
                            : '-'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('projeto.cidade.nome')
                    ->label('CIDADE')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('projeto.estado.nome')
                    ->label('UF')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('projeto.marca')
                    ->label('BANDEIRA')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.pipeline')
                    ->label('PIPE')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('fornecedor.nome')
                    ->label('CONSTRUTORA')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.area_academia')
                    ->label('ÁREA')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('situacao')
                    ->label('SITUAÇÃO')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'inaugurada' => 'Inaugurada',
                        'em_processo' => 'Em processo',
                        'em_obras' => 'Em Obra',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'inaugurada' => 'success',
                        'em_processo' => 'warning',
                        'em_obras' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('responsavelOrc.name')
                    ->label('RESPONSÁVEL - ORC')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.inicio_obra')
                    ->label('I.O.')
                    ->date('d/m/Y')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.entrega_obra')
                    ->label('E.O.')
                    ->date('d/m/Y')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.inauguracao')
                    ->label('INAUGURAÇÃO')
                    ->date('d/m/Y')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('dias_eo')
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

                Tables\Columns\TextColumn::make('elaboracao_contrato')
                    ->label('Elaboração Contrato')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status CNPJ')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'definitivo' => 'CNPJ Definitivo',
                        'provisorio' => 'CNPJ Provisório',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'definitivo' => 'success',
                        'provisorio' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('contratacao')
                    ->label('Contratação')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('observacoes')
                    ->label('Observações')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('valor_oi')
                    ->label('Valor OI')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('valor_realizado')
                    ->label('Valor Realizado')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('realizado_nf')
                    ->label('Realizado NF')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('BRL')
                    ->badge()
                    ->color(fn ($state) => $state < 0 ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('gestorObra.name')
                    ->label('Gestor Obra')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tamanho')
                    ->label('Tamanho')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('numero')
                    ->label('Número')
                    ->toggleable(isToggledHiddenByDefault: true),

                ProgressColumn::make('percentual')
                    ->label('% Pedidos Contratados')
                    ->getStateUsing(function ($record) {

                        $pedidos = $record->pedidos;

                        if (! is_array($pedidos)) {
                            return 0;
                        }

                        $total = count($pedidos);
                        $concluidos = collect($pedidos)
                            ->filter(fn ($valor) => $valor === true)
                            ->count();

                        return $total > 0
                            ? intval(($concluidos / $total) * 100)
                            : 0;
                    })
                    ->sortable(),

            ])
            ->striped()
            ->defaultSort('id', 'desc');
    }

    public static function canAccess(): bool
    {

        return auth()->user()?->can('View:DashboardPedidos');
    }

    public static function shouldRegisterNavigation(): bool
    {

        return auth()->user()?->can('View:DashboardPedidos');
    }
}
