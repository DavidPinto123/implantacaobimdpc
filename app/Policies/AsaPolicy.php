<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Asa;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AsaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Asa');
    }

    public function view(AuthUser $authUser, Asa $asa): bool
    {
        return $authUser->can('View:Asa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Asa');
    }

    public function update(AuthUser $authUser, Asa $asa): bool
    {
        return $authUser->can('Update:Asa');
    }

    public function delete(AuthUser $authUser, Asa $asa): bool
    {
        return $authUser->can('Delete:Asa');
    }

    public function restore(AuthUser $authUser, Asa $asa): bool
    {
        return $authUser->can('Restore:Asa');
    }

    public function forceDelete(AuthUser $authUser, Asa $asa): bool
    {
        return $authUser->can('ForceDelete:Asa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Asa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Asa');
    }

    public function replicate(AuthUser $authUser, Asa $asa): bool
    {
        return $authUser->can('Replicate:Asa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Asa');
    }
}
