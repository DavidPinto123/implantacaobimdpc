<?php

namespace App\Filament\Resources\Asas;

use App\Enums\AsStatus;
use App\Filament\Resources\Asas\Pages\CreateAsa;
use App\Filament\Resources\Asas\Pages\EditAsa;
use App\Filament\Resources\Asas\Pages\ListAsas;
use App\Filament\Resources\Asas\Schemas\AsaForm;
use App\Filament\Resources\Asas\Tables\AsasTable;
use App\Models\Asa;
use App\Support\AsaAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AsaResource extends Resource
{
    protected static ?string $model = Asa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?int $navigationSort = 4;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Engenharia';

    protected static ?string $navigationLabel = 'ASA';

    public static function form(Schema $schema): Schema
    {
        return AsaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsasTable::configure($table);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        $query = parent::getEloquentQuery()
            ->where(function (EloquentBuilder $builder): void {
                $builder
                    ->where(function (EloquentBuilder $unlinkedQuery): void {
                        $unlinkedQuery
                            ->whereNull('controle_nota_fiscal_auxiliar_id')
                            ->whereDoesntHave('controlesNotaFiscal');
                    })
                    ->orWhereHas('controleNotaFiscalAuxiliar.controleNotaFiscal.obra', function (EloquentBuilder $obraQuery): void {
                        $obraQuery->whereNull('obras.deleted_at');
                    })
                    ->orWhereHas('controlesNotaFiscal.obra', function (EloquentBuilder $obraQuery): void {
                        $obraQuery->whereNull('obras.deleted_at');
                    });
            });

        $user = Auth::user();

        if (! $user) {
            return $query;
        }

        if (AsaAccess::canViewAllStatuses($user)) {
            return $query;
        }

        if (AsaAccess::shouldRestrictToOrcamentoStatuses($user)) {
            return AsaAccess::scopeOnlyOrcamentoStatuses($query);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $isSuperAdmin = $user->hasRole('super_admin');

        $isGestorObras =
            $user->hasAnyRole(['Gestor', 'Coordenador']) &&
            $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();

        $isOrcamento =
            $user->hasAnyRole(['coordenador_orcamento', 'Coordenador', 'Coordenador_Orcamento', 'coordenador']) ||
            $user->setores()
                ->whereRaw('LOWER(setor) in (?, ?, ?, ?)', ['orçamento', 'orcamento', 'orçamentos', 'orcamentos'])
                ->exists();

        // Super admin enxerga as duas filas.
        if ($isSuperAdmin) {
            $countSolicitado = static::getEloquentQuery()
                ->where('status', AsStatus::SOLICITADO->value)
                ->count();

            $countOrcamento = static::getEloquentQuery()
                ->where('status', AsStatus::EM_APROVACAO_ORCAMENTO->value)
                ->count();

            $count = $countSolicitado + $countOrcamento;
        } elseif ($isOrcamento) {
            $count = static::getEloquentQuery()
                ->where('status', AsStatus::EM_APROVACAO_ORCAMENTO->value)
                ->count();
        } elseif ($isGestorObras) {
            $count = static::getEloquentQuery()
                ->where('status', AsStatus::SOLICITADO->value)
                ->count();
        } else {
            // Fora dessas filas, não exibe badge.
            $count = 0;
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAsas::route('/'),
            // A criação manual de ASA foi desativada. A entrada oficial passa a ser
            // apenas pelo fluxo de aditivo, que instancia a ASA via AsaService.
            // 'create' => CreateAsa::route('/create'),
            'edit' => EditAsa::route('/{record}/edit'),
        ];
    }
}
