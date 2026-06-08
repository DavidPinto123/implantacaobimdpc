<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Departamentos;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DepartamentosPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Departamentos');
    }

    public function view(AuthUser $authUser, Departamentos $departamentos): bool
    {
        return $authUser->can('View:Departamentos');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Departamentos');
    }

    public function update(AuthUser $authUser, Departamentos $departamentos): bool
    {
        return $authUser->can('Update:Departamentos');
    }

    public function delete(AuthUser $authUser, Departamentos $departamentos): bool
    {
        return $authUser->can('Delete:Departamentos');
    }

    public function restore(AuthUser $authUser, Departamentos $departamentos): bool
    {
        return $authUser->can('Restore:Departamentos');
    }

    public function forceDelete(AuthUser $authUser, Departamentos $departamentos): bool
    {
        return $authUser->can('ForceDelete:Departamentos');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Departamentos');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Departamentos');
    }

    public function replicate(AuthUser $authUser, Departamentos $departamentos): bool
    {
        return $authUser->can('Replicate:Departamentos');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Departamentos');
    }
}
