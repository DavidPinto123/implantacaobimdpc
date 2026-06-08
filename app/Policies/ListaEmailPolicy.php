<?php

namespace App\Policies;

use App\Models\ListaEmail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ListaEmailPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ListaEmail');
    }

    public function view(AuthUser $authUser, ListaEmail $listaEmail): bool
    {
        return $authUser->can('View:ListaEmail');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ListaEmail');
    }

    public function update(AuthUser $authUser, ListaEmail $listaEmail): bool
    {
        return $authUser->can('Update:ListaEmail');
    }

    public function delete(AuthUser $authUser, ListaEmail $listaEmail): bool
    {
        return $authUser->can('Delete:ListaEmail');
    }

    public function restore(AuthUser $authUser, ListaEmail $listaEmail): bool
    {
        return $authUser->can('Restore:ListaEmail');
    }

    public function forceDelete(AuthUser $authUser, ListaEmail $listaEmail): bool
    {
        return $authUser->can('ForceDelete:ListaEmail');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ListaEmail');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ListaEmail');
    }

    public function replicate(AuthUser $authUser, ListaEmail $listaEmail): bool
    {
        return $authUser->can('Replicate:ListaEmail');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ListaEmail');
    }
}
