<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Construtora;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ConstrutoraPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Construtora');
    }

    public function view(AuthUser $authUser, Construtora $construtora): bool
    {
        return $authUser->can('View:Construtora');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Construtora');
    }

    public function update(AuthUser $authUser, Construtora $construtora): bool
    {
        return $authUser->can('Update:Construtora');
    }

    public function delete(AuthUser $authUser, Construtora $construtora): bool
    {
        return $authUser->can('Delete:Construtora');
    }

    public function restore(AuthUser $authUser, Construtora $construtora): bool
    {
        return $authUser->can('Restore:Construtora');
    }

    public function forceDelete(AuthUser $authUser, Construtora $construtora): bool
    {
        return $authUser->can('ForceDelete:Construtora');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Construtora');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Construtora');
    }

    public function replicate(AuthUser $authUser, Construtora $construtora): bool
    {
        return $authUser->can('Replicate:Construtora');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Construtora');
    }
}
