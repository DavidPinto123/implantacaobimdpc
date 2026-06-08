<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Etapa;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EtapaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Etapa');
    }

    public function view(AuthUser $authUser, Etapa $etapa): bool
    {
        return $authUser->can('View:Etapa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Etapa');
    }

    public function update(AuthUser $authUser, Etapa $etapa): bool
    {
        return $authUser->can('Update:Etapa');
    }

    public function delete(AuthUser $authUser, Etapa $etapa): bool
    {
        return $authUser->can('Delete:Etapa');
    }

    public function restore(AuthUser $authUser, Etapa $etapa): bool
    {
        return $authUser->can('Restore:Etapa');
    }

    public function forceDelete(AuthUser $authUser, Etapa $etapa): bool
    {
        return $authUser->can('ForceDelete:Etapa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Etapa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Etapa');
    }

    public function replicate(AuthUser $authUser, Etapa $etapa): bool
    {
        return $authUser->can('Replicate:Etapa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Etapa');
    }
}
