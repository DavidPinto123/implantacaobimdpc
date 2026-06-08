<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ambientes;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AmbientesPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Ambientes');
    }

    public function view(AuthUser $authUser, Ambientes $ambientes): bool
    {
        return $authUser->can('View:Ambientes');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Ambientes');
    }

    public function update(AuthUser $authUser, Ambientes $ambientes): bool
    {
        return $authUser->can('Update:Ambientes');
    }

    public function delete(AuthUser $authUser, Ambientes $ambientes): bool
    {
        return $authUser->can('Delete:Ambientes');
    }

    public function restore(AuthUser $authUser, Ambientes $ambientes): bool
    {
        return $authUser->can('Restore:Ambientes');
    }

    public function forceDelete(AuthUser $authUser, Ambientes $ambientes): bool
    {
        return $authUser->can('ForceDelete:Ambientes');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Ambientes');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Ambientes');
    }

    public function replicate(AuthUser $authUser, Ambientes $ambientes): bool
    {
        return $authUser->can('Replicate:Ambientes');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Ambientes');
    }
}
