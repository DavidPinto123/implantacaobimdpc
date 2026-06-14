<?php

namespace App\Filament\Resources\AutorizacaoServicos;

use App\Filament\Resources\AutorizacaoServicos\Pages\ControleAutorizacoesServico;
use App\Filament\Resources\AutorizacaoServicos\Pages\EditAutorizacaoServico;
use App\Filament\Resources\AutorizacaoServicos\Schemas\AutorizacaoServicoForm;
use App\Filament\Resources\AutorizacaoServicos\Tables\AutorizacaoServicosTable;
use App\Models\AutorizacaoServico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AutorizacaoServicoResource extends Resource
{
    protected static ?string $model = AutorizacaoServico::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Orçamentos';

    protected static ?string $navigationLabel = 'Controle de AS';

    protected static ?string $modelLabel = 'AS';

    protected static ?string $pluralModelLabel = 'Autorizações de Serviço';

    protected static ?string $slug = 'autorizacoes-servico';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return AutorizacaoServicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AutorizacaoServicosTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('obra', fn (Builder $obraQuery): Builder => $obraQuery->whereNull('obras.deleted_at'));
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
            'index' => ControleAutorizacoesServico::route('/'),
            'edit' => EditAutorizacaoServico::route('/{record}/edit'),
        ];
    }
}
