<?php

namespace App\Filament\Resources\ImportacaoNotaFiscals;

use App\Filament\Resources\ImportacaoNotaFiscals\Pages\CreateImportacaoNotaFiscal;
use App\Filament\Resources\ImportacaoNotaFiscals\Pages\EditImportacaoNotaFiscal;
use App\Filament\Resources\ImportacaoNotaFiscals\Pages\ListImportacaoNotaFiscals;
use App\Filament\Resources\ImportacaoNotaFiscals\Schemas\ImportacaoNotaFiscalForm;
use App\Filament\Resources\ImportacaoNotaFiscals\Schemas\ImportacaoNotaFiscalInfolist;
use App\Filament\Resources\ImportacaoNotaFiscals\Tables\ImportacaoNotaFiscalsTable;
use App\Models\ControleNotaFiscalNota;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ImportacaoNotaFiscalResource extends Resource
{
    protected static ?string $model = ControleNotaFiscalNota::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static ?string $navigationLabel = 'Importação de Notas Fiscais';

    protected static ?string $modelLabel = 'Importação de Nota Fiscal';

    protected static ?string $pluralModelLabel = 'Importação de Notas Fiscais';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Fornecedor';

    protected static ?string $slug = 'importacao-notas-fiscais';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ImportacaoNotaFiscalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImportacaoNotaFiscalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportacaoNotaFiscalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportacaoNotaFiscals::route('/'),
            'create' => CreateImportacaoNotaFiscal::route('/create'),
            'edit' => EditImportacaoNotaFiscal::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNotNull('autorizacao_servico_id')
                    ->orWhereNotNull('autorizacao_servico_adicional_id');
            })
            ->where(function (Builder $builder): void {
                $builder
                    ->whereHas('autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra', function (Builder $obraQuery): void {
                        $obraQuery->whereNull('obras.deleted_at');
                    })
                    ->orWhereHas('asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra', function (Builder $obraQuery): void {
                        $obraQuery->whereNull('obras.deleted_at');
                    });
            })
            ->with([
                'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra',
                'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra',
            ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if (! static::isConstrutoraTerceirosUser($user)) {
            return $query;
        }

        return $query->where('importado_por_id', $user->id);
    }

    public static function isConstrutoraTerceirosUser(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('Fornecedor')
            && filled($user->construtoras_id)
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['terceiros fornecedor'])->exists();
    }
}
