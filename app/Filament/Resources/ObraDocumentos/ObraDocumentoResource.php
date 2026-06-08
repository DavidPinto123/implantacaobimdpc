<?php

namespace App\Filament\Resources\ObraDocumentos;

use App\Filament\Resources\ObraDocumentos\Pages\EditObraDocumento;
use App\Filament\Resources\ObraDocumentos\Pages\ListObraDocumentos;
use App\Filament\Resources\ObraDocumentos\Schemas\ObraDocumentoForm;
use App\Filament\Resources\ObraDocumentos\Schemas\ObraDocumentoInfolist;
use App\Filament\Resources\ObraDocumentos\Tables\ObraDocumentosTable;
use App\Models\ObraDocumento;
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

class ObraDocumentoResource extends Resource
{
    protected static ?string $model = ObraDocumento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Envio de Documentos';

    protected static ?string $modelLabel = 'Documento de Obra';

    protected static ?string $pluralModelLabel = 'Documentos de Obra';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Fornecedor';

    protected static ?string $slug = 'envio-documentos';

    public static function form(Schema $schema): Schema
    {
        return ObraDocumentoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ObraDocumentoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObraDocumentosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObraDocumentos::route('/'),
            'edit' => EditObraDocumento::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'obra:id,projeto_id',
            'obra.projeto:id,nome,sigla,nova_sigla',
            'usuario:id,name',
        ]);

        // O fornecedor só enxerga documentos atribuídos a ela.
        $user = Auth::user();
        if (
            $user instanceof User
            && static::isConstrutoraTerceiros($user)
            && filled($user->construtoras_id)
        ) {
            $query->where('construtora_id', $user->construtoras_id);
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
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
        $query = Obras::query()
            ->with(['projeto:id,nome,sigla,nova_sigla'])
            ->orderBy('id');

        if (static::canManageAll($user)) {
            return $query;
        }

        if (static::isConstrutoraTerceiros($user) && filled($user?->construtoras_id)) {
            return $query->whereHas('construtoras', function (Builder $builder) use ($user): void {
                $builder->where('construtoras.id', $user->construtoras_id);
            });
        }

        return $query->whereRaw('1 = 0');
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

    public static function getUploadDisk(): string
    {
        return (string) config('filesystems.media_disk', 'r2');
    }

    public static function getStatusOptions(): array
    {
        return [
            'pendente' => 'Pendente',
            'enviado' => 'Enviado',
            'nao_aplicavel' => 'Enviado',
        ];
    }

    public static function getStatusLabel(?string $status): string
    {
        if ($status === 'nao_aplicavel') {
            return 'Enviado';
        }

        return static::getStatusOptions()[$status] ?? (string) $status;
    }

    public static function isSentStatus(?string $status): bool
    {
        return in_array($status, ['enviado', 'nao_aplicavel'], true);
    }

    public static function getStatusColor(?string $status): string
    {
        return static::isSentStatus($status) ? 'success' : 'warning';
    }
}
