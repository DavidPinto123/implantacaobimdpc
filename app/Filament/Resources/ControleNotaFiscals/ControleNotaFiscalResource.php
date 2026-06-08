<?php

namespace App\Filament\Resources\ControleNotaFiscals;

use App\Filament\Resources\ControleNotaFiscals\Pages\EditControleNotaFiscal;
use App\Filament\Resources\ControleNotaFiscals\Pages\ListControleNotaFiscals;
use App\Filament\Resources\ControleNotaFiscals\Schemas\ControleNotaFiscalForm;
use App\Filament\Resources\ControleNotaFiscals\Schemas\ControleNotaFiscalInfolist;
use App\Filament\Resources\ControleNotaFiscals\Tables\ControleNotaFiscalsTable;
use App\Models\ControleNotaFiscal;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ControleNotaFiscalResource extends Resource
{
    protected static ?string $model = ControleNotaFiscal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Controle de Notas Fiscais';

    protected static ?string $modelLabel = 'Controle de Nota Fiscal';

    protected static ?string $pluralModelLabel = 'Controle de Notas Fiscais';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Engenharia';

    protected static ?string $slug = 'controle-notas-fiscais';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return ControleNotaFiscalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ControleNotaFiscalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ControleNotaFiscalsTable::configure($table);
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
            'index' => ListControleNotaFiscals::route('/'),
            'edit' => EditControleNotaFiscal::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->whereHas('obra', fn (Builder $obraQuery): Builder => $obraQuery->whereNull('obras.deleted_at'));

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $isSuperAdmin = $user->hasRole('super_admin');

        $isGestorObras =
            $user->hasRole('Gestor')
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();

        $isCoordObras =
            $user->hasRole('Coordenador')
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();

        $isConstrutoraTerceiros =
            $user->hasRole('Fornecedor')
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['terceiros fornecedor'])->exists();

        $isOrcamento =
            $user->hasAnyRole(['coordenador_orcamento', 'colaborador_orcamento'])
            || $user->setores()->whereRaw('LOWER(setor) in (?, ?, ?, ?)', ['orçamento', 'orcamento', 'orçamentos', 'orcamentos'])->exists();

        if ($isSuperAdmin || $isGestorObras || $isCoordObras || $isOrcamento) {
            return $query;
        }

        if ($isConstrutoraTerceiros && $user->construtoras_id) {
            $construtoraNome = $user->construtora?->nome;

            if (blank($construtoraNome)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function (Builder $builder) use ($construtoraNome): void {
                $builder
                    ->whereHas('itens', function (Builder $itemQuery) use ($construtoraNome): void {
                        $itemQuery->where('empresa', $construtoraNome);
                    })
                    ->orWhereHas('auxiliares', function (Builder $auxiliarQuery) use ($construtoraNome): void {
                        $auxiliarQuery->where('empresa', $construtoraNome);
                    });
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
