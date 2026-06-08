<?php

namespace App\Filament\Resources\ElaboracaoAditivos;

use App\Filament\Resources\ElaboracaoAditivos\Pages\CreateElaboracaoAditivo;
use App\Filament\Resources\ElaboracaoAditivos\Pages\EditElaboracaoAditivo;
use App\Filament\Resources\ElaboracaoAditivos\Pages\ListElaboracaoAditivos;
use App\Filament\Resources\ElaboracaoAditivos\Schemas\ElaboracaoAditivoForm;
use App\Filament\Resources\ElaboracaoAditivos\Tables\ElaboracaoAditivosTable;
use App\Models\ControleNotaFiscalItem;
use App\Models\ElaboracaoAditivo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ElaboracaoAditivoResource extends Resource
{
    protected static ?string $model = ElaboracaoAditivo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Elaboração de Aditivos';

    protected static ?string $modelLabel = 'Elaboração de Aditivo';

    protected static ?string $pluralModelLabel = 'Elaboração de Aditivos';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Fornecedor';

    protected static ?string $slug = 'elaboracao-aditivos';

    public static function form(Schema $schema): Schema
    {
        return ElaboracaoAditivoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ElaboracaoAditivosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function opcoesRefServicoPorObra(?int $obraId): array
    {
        if (! $obraId) {
            return [];
        }

        return ControleNotaFiscalItem::query()
            ->whereNotNull('as_escopo_id')
            ->whereHas('controleNotaFiscal', fn (Builder $query): Builder => $query->where('obra_id', $obraId))
            ->with('asEscopo:id,escopo')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'controle_nota_fiscal_id', 'as_escopo_id', 'escopo'])
            ->unique('as_escopo_id')
            ->mapWithKeys(fn (ControleNotaFiscalItem $item): array => [
                (int) $item->as_escopo_id => (string) ($item->escopo ?: $item->asEscopo?->escopo ?: "Escopo #{$item->as_escopo_id}"),
            ])
            ->sort()
            ->all();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $isSuperAdmin = $user->hasRole('super_admin');

        $isGestorObras =
            $user->hasRole('Gestor') &&
            $user->setores()->where('setor', 'Obras')->exists();

        $isConstrutoraTerceiros =
            $user->hasRole('Fornecedor') &&
            $user->setores()->where('setor', 'Terceiros Fornecedor')->exists();

        if ($isSuperAdmin || $isGestorObras) {
            return $query;
        }

        if ($isConstrutoraTerceiros && $user->construtoras_id) {
            return $query->where('construtora_id', $user->construtoras_id);
        }

        return $query->where('user_id', $user->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListElaboracaoAditivos::route('/'),
            'create-custom' => Pages\CreateAditivo::route('/criar'),
            // 'create' => CreateElaboracaoAditivo::route('/create'),
            'edit' => EditElaboracaoAditivo::route('/{record}/edit'),
            'visualizar' => Pages\ViewElaboracaoAditivoCustom::route('/{record}/visualizar'),
        ];
    }
}
