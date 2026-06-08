<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Matterport;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MatterportPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Matterport');
    }

    public function view(AuthUser $authUser, Matterport $matterport): bool
    {
        return $authUser->can('View:Matterport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Matterport');
    }

    public function update(AuthUser $authUser, Matterport $matterport): bool
    {
        return $authUser->can('Update:Matterport');
    }

    public function delete(AuthUser $authUser, Matterport $matterport): bool
    {
        return $authUser->can('Delete:Matterport');
    }

    public function restore(AuthUser $authUser, Matterport $matterport): bool
    {
        return $authUser->can('Restore:Matterport');
    }

    public function forceDelete(AuthUser $authUser, Matterport $matterport): bool
    {
        return $authUser->can('ForceDelete:Matterport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Matterport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Matterport');
    }

    public function replicate(AuthUser $authUser, Matterport $matterport): bool
    {
        return $authUser->can('Replicate:Matterport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Matterport');
    }
}
