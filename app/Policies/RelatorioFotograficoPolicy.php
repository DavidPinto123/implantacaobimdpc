<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RelatorioFotografico;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RelatorioFotograficoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RelatorioFotografico');
    }

    public function view(AuthUser $authUser, RelatorioFotografico $relatorioFotografico): bool
    {
        return $authUser->can('View:RelatorioFotografico');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RelatorioFotografico');
    }

    public function update(AuthUser $authUser, RelatorioFotografico $relatorioFotografico): bool
    {
        return $authUser->can('Update:RelatorioFotografico');
    }

    public function delete(AuthUser $authUser, RelatorioFotografico $relatorioFotografico): bool
    {
        return $authUser->can('Delete:RelatorioFotografico');
    }

    public function restore(AuthUser $authUser, RelatorioFotografico $relatorioFotografico): bool
    {
        return $authUser->can('Restore:RelatorioFotografico');
    }

    public function forceDelete(AuthUser $authUser, RelatorioFotografico $relatorioFotografico): bool
    {
        return $authUser->can('ForceDelete:RelatorioFotografico');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RelatorioFotografico');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RelatorioFotografico');
    }

    public function replicate(AuthUser $authUser, RelatorioFotografico $relatorioFotografico): bool
    {
        return $authUser->can('Replicate:RelatorioFotografico');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RelatorioFotografico');
    }
}
