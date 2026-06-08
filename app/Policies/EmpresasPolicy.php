<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Empresas;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EmpresasPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Empresas');
    }

    public function view(AuthUser $authUser, Empresas $empresas): bool
    {
        return $authUser->can('View:Empresas');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Empresas');
    }

    public function update(AuthUser $authUser, Empresas $empresas): bool
    {
        return $authUser->can('Update:Empresas');
    }

    public function delete(AuthUser $authUser, Empresas $empresas): bool
    {
        return $authUser->can('Delete:Empresas');
    }

    public function restore(AuthUser $authUser, Empresas $empresas): bool
    {
        return $authUser->can('Restore:Empresas');
    }

    public function forceDelete(AuthUser $authUser, Empresas $empresas): bool
    {
        return $authUser->can('ForceDelete:Empresas');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Empresas');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Empresas');
    }

    public function replicate(AuthUser $authUser, Empresas $empresas): bool
    {
        return $authUser->can('Replicate:Empresas');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Empresas');
    }
}
