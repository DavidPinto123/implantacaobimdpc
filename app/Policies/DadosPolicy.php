<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Dados;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DadosPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Dados');
    }

    public function view(AuthUser $authUser, Dados $dados): bool
    {
        return $authUser->can('View:Dados');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Dados');
    }

    public function update(AuthUser $authUser, Dados $dados): bool
    {
        return $authUser->can('Update:Dados');
    }

    public function delete(AuthUser $authUser, Dados $dados): bool
    {
        return $authUser->can('Delete:Dados');
    }

    public function restore(AuthUser $authUser, Dados $dados): bool
    {
        return $authUser->can('Restore:Dados');
    }

    public function forceDelete(AuthUser $authUser, Dados $dados): bool
    {
        return $authUser->can('ForceDelete:Dados');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Dados');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Dados');
    }

    public function replicate(AuthUser $authUser, Dados $dados): bool
    {
        return $authUser->can('Replicate:Dados');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Dados');
    }
}
