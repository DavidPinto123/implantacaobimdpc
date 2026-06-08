<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AutorizacaoServico;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AutorizacaoServicoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AutorizacaoServico');
    }

    public function view(AuthUser $authUser, AutorizacaoServico $autorizacaoServico): bool
    {
        return $authUser->can('View:AutorizacaoServico');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AutorizacaoServico');
    }

    public function update(AuthUser $authUser, AutorizacaoServico $autorizacaoServico): bool
    {
        return $authUser->can('Update:AutorizacaoServico');
    }

    public function delete(AuthUser $authUser, AutorizacaoServico $autorizacaoServico): bool
    {
        return $authUser->can('Delete:AutorizacaoServico');
    }

    public function restore(AuthUser $authUser, AutorizacaoServico $autorizacaoServico): bool
    {
        return $authUser->can('Restore:AutorizacaoServico');
    }

    public function forceDelete(AuthUser $authUser, AutorizacaoServico $autorizacaoServico): bool
    {
        return $authUser->can('ForceDelete:AutorizacaoServico');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AutorizacaoServico');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AutorizacaoServico');
    }

    public function replicate(AuthUser $authUser, AutorizacaoServico $autorizacaoServico): bool
    {
        return $authUser->can('Replicate:AutorizacaoServico');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AutorizacaoServico');
    }
}
