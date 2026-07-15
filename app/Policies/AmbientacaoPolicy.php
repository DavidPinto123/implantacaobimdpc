<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Ambientacao;
use Illuminate\Auth\Access\HandlesAuthorization;

class AmbientacaoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Ambientacao');
    }

    public function view(AuthUser $authUser, Ambientacao $ambientacao): bool
    {
        return $authUser->can('View:Ambientacao');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Ambientacao');
    }

    public function update(AuthUser $authUser, Ambientacao $ambientacao): bool
    {
        return $authUser->can('Update:Ambientacao');
    }

    public function delete(AuthUser $authUser, Ambientacao $ambientacao): bool
    {
        return $authUser->can('Delete:Ambientacao');
    }

    public function restore(AuthUser $authUser, Ambientacao $ambientacao): bool
    {
        return $authUser->can('Restore:Ambientacao');
    }

    public function forceDelete(AuthUser $authUser, Ambientacao $ambientacao): bool
    {
        return $authUser->can('ForceDelete:Ambientacao');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Ambientacao');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Ambientacao');
    }

    public function replicate(AuthUser $authUser, Ambientacao $ambientacao): bool
    {
        return $authUser->can('Replicate:Ambientacao');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Ambientacao');
    }

}