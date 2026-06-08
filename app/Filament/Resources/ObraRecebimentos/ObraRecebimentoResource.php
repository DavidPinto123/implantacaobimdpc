<?php

namespace App\Filament\Resources\ObraRecebimentos;

use App\Filament\Resources\ObraRecebimentos\Pages\EditObraRecebimento;
use App\Filament\Resources\ObraRecebimentos\Pages\ListObraRecebimentos;
use App\Filament\Resources\ObraRecebimentos\Schemas\ObraRecebimentoForm;
use App\Filament\Resources\ObraRecebimentos\Schemas\ObraRecebimentoInfolist;
use App\Filament\Resources\ObraRecebimentos\Tables\ObraRecebimentosTable;
use App\Models\ObraRecebimento;
use App\Models\Obras;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ObraRecebimentoResource extends Resource
{
    protected static ?string $model = ObraRecebimento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Controle de Recebimentos';

    protected static ?string $modelLabel = 'Controle de Recebimento';

    protected static ?string $pluralModelLabel = 'Controle de Recebimentos';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Fornecedor';

    protected static ?string $slug = 'controle-recebimentos';

    public static function form(Schema $schema): Schema
    {
        return ObraRecebimentoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ObraRecebimentoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObraRecebimentosTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'obra:id,projeto_id',
            'obra.projeto:id,nome,sigla,nova_sigla',
            'construtora:id,nome',
            'usuario:id,name',
        ]);
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
            'index' => ListObraRecebimentos::route('/'),
            'edit' => EditObraRecebimento::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return static::canManageAll(Auth::user());
    }

    public static function canDelete($record): bool
    {
        return static::canManageAll(Auth::user());
    }

    public static function canDeleteAny(): bool
    {
        return static::canManageAll(Auth::user());
    }

    public static function canManageAll(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('Gestor')
            && $user->setores()->where('setor', 'Obras')->exists();
    }

    public static function isConstrutoraTerceiros(?User $user): bool
    {
        return $user instanceof User
            && $user->hasRole('Fornecedor')
            && $user->setores()->where('setor', 'Terceiros Fornecedor')->exists();
    }

    public static function getAvailableObrasQuery(?User $user): Builder
    {
        if ($user === null) {
        }

        $query = Obras::query()
            ->with(['projeto:id,nome,sigla,nova_sigla'])
            ->orderBy('id');

        return $query;
    }

    public static function getAvailableObrasOptions(?User $user): array
    {
        return static::getAvailableObrasQuery($user)
            ->get(['id', 'projeto_id'])
            ->mapWithKeys(fn (Obras $obra): array => [
                $obra->id => static::getObraLabel($obra),
            ])
            ->all();
    }

    public static function getObraLabel(Obras $obra): string
    {
        $projeto = $obra->projeto;

        if ($projeto !== null) {
            $label = trim(implode(' - ', array_filter([
                $projeto->nova_sigla,
                $projeto->sigla,
                $projeto->nome,
            ])));

            if ($label !== '') {
                return $label;
            }
        }

        return "Obra #{$obra->id}";
    }

    public static function resolveConstrutoraIdForObra(mixed $obraId, ?User $user): ?int
    {
        if (static::isConstrutoraTerceiros($user) && filled($user?->construtoras_id)) {
            return (int) $user->construtoras_id;
        }

        if (! filled($obraId)) {
            return null;
        }

        $obra = Obras::query()->find($obraId);

        if (! $obra) {
            return null;
        }

        $construtoraId = $obra->construtoras()
            ->orderBy('construtoras.id')
            ->value('construtoras.id');

        return $construtoraId ? (int) $construtoraId : null;
    }

    public static function getUploadDisk(): string
    {
        return (string) config('filesystems.media_disk', 'r2');
    }

    public static function getStatusOptions(): array
    {
        return [
            'pendente' => 'Pendente',
            'recebido' => 'Recebido',
            'nao_aplicavel' => 'Recebido',
        ];
    }

    public static function getStatusLabel(?string $status): string
    {
        if ($status === 'nao_aplicavel') {
            return 'Recebido';
        }

        return static::getStatusOptions()[$status] ?? (string) $status;
    }

    public static function isReceivedStatus(?string $status): bool
    {
        return in_array($status, ['recebido', 'nao_aplicavel'], true);
    }

    public static function getStatusColor(?string $status): string
    {
        return static::isReceivedStatus($status) ? 'success' : 'warning';
    }

    public static function getPendingRecebimentosOptionsForObra(mixed $obraId, ?User $user): array
    {
        if ($user === null) {
        }

        if (! filled($obraId)) {
            return [];
        }

        $query = ObraRecebimento::query()
            ->where('obra_id', $obraId)
            ->where('status', 'pendente')
            ->orderBy('nome');

        return $query
            ->get(['id', 'nome'])
            ->mapWithKeys(fn (ObraRecebimento $recebimento): array => [
                $recebimento->id => $recebimento->nome,
            ])
            ->all();
    }
}
