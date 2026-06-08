<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ElaboracaoAditivo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ElaboracaoAditivoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ElaboracaoAditivo');
    }

    public function view(AuthUser $authUser, ElaboracaoAditivo $elaboracaoAditivo): bool
    {
        return $authUser->can('View:ElaboracaoAditivo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ElaboracaoAditivo');
    }

    public function update(AuthUser $authUser, ElaboracaoAditivo $elaboracaoAditivo): bool
    {
        return $authUser->can('Update:ElaboracaoAditivo');
    }

    public function delete(AuthUser $authUser, ElaboracaoAditivo $elaboracaoAditivo): bool
    {
        return $authUser->can('Delete:ElaboracaoAditivo');
    }

    public function restore(AuthUser $authUser, ElaboracaoAditivo $elaboracaoAditivo): bool
    {
        return $authUser->can('Restore:ElaboracaoAditivo');
    }

    public function forceDelete(AuthUser $authUser, ElaboracaoAditivo $elaboracaoAditivo): bool
    {
        return $authUser->can('ForceDelete:ElaboracaoAditivo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ElaboracaoAditivo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ElaboracaoAditivo');
    }

    public function replicate(AuthUser $authUser, ElaboracaoAditivo $elaboracaoAditivo): bool
    {
        return $authUser->can('Replicate:ElaboracaoAditivo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ElaboracaoAditivo');
    }
}
