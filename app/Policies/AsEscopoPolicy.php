<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AsEscopo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AsEscopoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AsEscopo');
    }

    public function view(AuthUser $authUser, AsEscopo $asEscopo): bool
    {
        return $authUser->can('View:AsEscopo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AsEscopo');
    }

    public function update(AuthUser $authUser, AsEscopo $asEscopo): bool
    {
        return $authUser->can('Update:AsEscopo');
    }

    public function delete(AuthUser $authUser, AsEscopo $asEscopo): bool
    {
        return $authUser->can('Delete:AsEscopo');
    }

    public function restore(AuthUser $authUser, AsEscopo $asEscopo): bool
    {
        return $authUser->can('Restore:AsEscopo');
    }

    public function forceDelete(AuthUser $authUser, AsEscopo $asEscopo): bool
    {
        return $authUser->can('ForceDelete:AsEscopo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AsEscopo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AsEscopo');
    }

    public function replicate(AuthUser $authUser, AsEscopo $asEscopo): bool
    {
        return $authUser->can('Replicate:AsEscopo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AsEscopo');
    }
}
