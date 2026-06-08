<?php

declare(strict_types=1);

namespace App\Policies\PosObra;

use App\Models\PosObra\Pendencia;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PendenciaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pendencia');
    }

    public function view(AuthUser $authUser, Pendencia $pendencia): bool
    {
        return $authUser->can('View:Pendencia');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pendencia');
    }

    public function update(AuthUser $authUser, Pendencia $pendencia): bool
    {
        return $authUser->can('Update:Pendencia');
    }

    public function delete(AuthUser $authUser, Pendencia $pendencia): bool
    {
        return $authUser->can('Delete:Pendencia');
    }

    public function restore(AuthUser $authUser, Pendencia $pendencia): bool
    {
        return $authUser->can('Restore:Pendencia');
    }

    public function forceDelete(AuthUser $authUser, Pendencia $pendencia): bool
    {
        return $authUser->can('ForceDelete:Pendencia');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pendencia');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pendencia');
    }

    public function replicate(AuthUser $authUser, Pendencia $pendencia): bool
    {
        return $authUser->can('Replicate:Pendencia');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pendencia');
    }
}
