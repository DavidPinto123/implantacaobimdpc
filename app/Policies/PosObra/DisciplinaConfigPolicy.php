<?php

declare(strict_types=1);

namespace App\Policies\PosObra;

use App\Models\PosObra\DisciplinaConfig;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DisciplinaConfigPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DisciplinaConfig');
    }

    public function view(AuthUser $authUser, DisciplinaConfig $disciplinaConfig): bool
    {
        return $authUser->can('View:DisciplinaConfig');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DisciplinaConfig');
    }

    public function update(AuthUser $authUser, DisciplinaConfig $disciplinaConfig): bool
    {
        return $authUser->can('Update:DisciplinaConfig');
    }

    public function delete(AuthUser $authUser, DisciplinaConfig $disciplinaConfig): bool
    {
        return $authUser->can('Delete:DisciplinaConfig');
    }

    public function restore(AuthUser $authUser, DisciplinaConfig $disciplinaConfig): bool
    {
        return $authUser->can('Restore:DisciplinaConfig');
    }

    public function forceDelete(AuthUser $authUser, DisciplinaConfig $disciplinaConfig): bool
    {
        return $authUser->can('ForceDelete:DisciplinaConfig');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DisciplinaConfig');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DisciplinaConfig');
    }

    public function replicate(AuthUser $authUser, DisciplinaConfig $disciplinaConfig): bool
    {
        return $authUser->can('Replicate:DisciplinaConfig');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DisciplinaConfig');
    }
}
