<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AmbienteRdc50;
use Illuminate\Auth\Access\HandlesAuthorization;

class AmbienteRdc50Policy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AmbienteRdc50');
    }

    public function view(AuthUser $authUser, AmbienteRdc50 $ambienteRdc50): bool
    {
        return $authUser->can('View:AmbienteRdc50');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AmbienteRdc50');
    }

    public function update(AuthUser $authUser, AmbienteRdc50 $ambienteRdc50): bool
    {
        return $authUser->can('Update:AmbienteRdc50');
    }

    public function delete(AuthUser $authUser, AmbienteRdc50 $ambienteRdc50): bool
    {
        return $authUser->can('Delete:AmbienteRdc50');
    }

    public function restore(AuthUser $authUser, AmbienteRdc50 $ambienteRdc50): bool
    {
        return $authUser->can('Restore:AmbienteRdc50');
    }

    public function forceDelete(AuthUser $authUser, AmbienteRdc50 $ambienteRdc50): bool
    {
        return $authUser->can('ForceDelete:AmbienteRdc50');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AmbienteRdc50');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AmbienteRdc50');
    }

    public function replicate(AuthUser $authUser, AmbienteRdc50 $ambienteRdc50): bool
    {
        return $authUser->can('Replicate:AmbienteRdc50');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AmbienteRdc50');
    }

}