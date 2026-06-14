<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Models\OrdemInvestimento;
use App\Models\Projeto;
use App\Tables\Columns\ProgressColumn;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DashboardOI extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.pages.dashboard-o-i';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Gestor OI';

    protected static ?string $title = 'Gestor OI';

    protected static ?int $navigationSort = 2;

    protected static string|null|UnitEnum $navigationGroup = 'Orçamentos';
    // URL: /admin/minha-pagina
    // protected static ?string $slug = 'dashboard-coordenador-orcamento';

    protected static bool $shouldRegisterNavigation = true;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([

                Tables\Columns\TextColumn::make('nova_sigla')
                    ->label('LOJA')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('UNIDADE'),

                BadgeColumn::make('ultimaOi.status_oi')
                    ->label('Status OI')
                    ->colors([
                        'warning' => 'em_aprovacao',
                        'success' => 'aprovada',
                        'danger' => 'reprovada',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'em_aprovacao' => 'Em Aprovação',
                        'aprovada' => 'Aprovada',
                        'reprovada' => 'Reprovada',
                        default => '-',
                    }),

                ProgressColumn::make('percentual_execucao')
                    ->label('% AS Concluídas')
                    ->getStateUsing(function ($record) {

                        // Pega último controle do projeto
                        $controle = $record->controlePedidos()
                            ->latest()
                            ->first();

                        if (! $controle) {
                            return 0;
                        }

                        $pedidos = $controle->pedidos;

                        if (! is_array($pedidos)) {
                            return 0;
                        }

                        $total = count($pedidos);

                        if ($total === 0) {
                            return 0;
                        }

                        $concluidos = collect($pedidos)
                            ->filter(fn ($valor) => $valor === true)
                            ->count();

                        return intval(($concluidos / $total) * 100);
                    }),

                Tables\Columns\TextColumn::make('ultimaOi.valor_total')
                    ->label('OI')
                    ->money('BRL'),

                Tables\Columns\TextColumn::make('ultimaOi.valor_pago')
                    ->label('PAGO')
                    ->money('BRL')
                    ->getStateUsing(
                        fn ($record) => $record->ultimaOi->valor_pago ?? 0
                    ),

                Tables\Columns\TextColumn::make('comprometido')
                    ->label('COMPROMETIDO')
                    ->money('BRL')
                    ->getStateUsing(function ($record) {

                        $oi = $record->ultimaOi;
                        if (! $oi) {
                            return 0;
                        }

                        // Buscar controle de pedidos do projeto
                        $controle = $record->controlePedidos()->latest()->first();
                        if (! $controle) {
                            return 0;
                        }

                        $estrutura = collect($oi->estrutura);
                        $pedidos = $controle->pedidos ?? [];

                        return $estrutura->sum(function ($linha) use ($pedidos) {

                            $map = ControlePedidoResource::pedidosMap();

                            // Descobrir código do pedido baseado no nome
                            $codigo = collect($map)
                                ->filter(fn ($codigos, $nome) => $nome === $linha['nome'])
                                ->flatten()
                                ->first();

                            if (! $codigo) {
                                return 0;
                            }

                            $codigoKey = str_replace('.', '_', $codigo);

                            $contratado = data_get($pedidos, $codigoKey, false);

                            if (! $contratado) {
                                return 0;
                            }

                            return (float) ($linha['padrao'] ?? 0)
                                + (float) ($linha['ad'] ?? 0);
                        });
                    }),

                Tables\Columns\TextColumn::make('saldo')
                    ->label('SALDO')
                    ->money('BRL')
                    ->getStateUsing(function ($record) {

                        $oi = $record->ultimaOi;
                        if (! $oi) {
                            return 0;
                        }

                        $valorTotal = (float) $oi->valor_total;
                        $valorPago = (float) ($oi->valor_pago ?? 0);

                        // calcular comprometido igual você já fez
                        $controle = $record->controlePedidos()->latest()->first();
                        if (! $controle) {
                            return $valorTotal - $valorPago;
                        }

                        $estrutura = collect($oi->estrutura);
                        $pedidos = $controle->pedidos ?? [];

                        $comprometido = $estrutura->sum(function ($linha) use ($pedidos) {

                            $map = ControlePedidoResource::pedidosMap();

                            $codigo = collect($map)
                                ->filter(fn ($codigos, $nome) => $nome === $linha['nome'])
                                ->flatten()
                                ->first();

                            if (! $codigo) {
                                return 0;
                            }

                            $codigoKey = str_replace('.', '_', $codigo);

                            $contratado = data_get($pedidos, $codigoKey, false);

                            if (! $contratado) {
                                return 0;
                            }

                            return (float) ($linha['padrao'] ?? 0)
                                + (float) ($linha['ad'] ?? 0);
                        });

                        return $valorTotal - $comprometido - $valorPago;
                    }),
            ])->actions([
                Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(
                        fn ($record) => $record->ultimaOi?->status_oi === 'em_aprovacao'
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {

                        $oi = OrdemInvestimento::where('projeto_id', $record->id)
                            ->latest()
                            ->first();

                        $oi->status_oi = 'aprovada';
                        $oi->save();
                    }),

                Action::make('reprovar')
                    ->label('Reprovar')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(
                        fn ($record) => $record->ultimaOi?->status_oi === 'em_aprovacao'
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {

                        $oi = OrdemInvestimento::where('projeto_id', $record->id)
                            ->latest()
                            ->first();

                        if (! $oi) {
                            return;
                        }

                        $oi->status_oi = 'reprovada';
                        $oi->save();
                    }),
            ]);
    }

    protected function getQuery(): Builder
    {
        return Projeto::query()
            ->whereHas('ordensInvestimento')
            ->with(['ultimaOi']);
    }

    public static function canAccess(): bool
    {

        return auth()->user()?->can('View:DashboardOI');
    }

    public static function shouldRegisterNavigation(): bool
    {

        return auth()->user()?->can('View:DashboardOI');
    }
}
