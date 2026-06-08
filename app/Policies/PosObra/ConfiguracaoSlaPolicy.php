<?php

declare(strict_types=1);

namespace App\Policies\PosObra;

use App\Models\PosObra\ConfiguracaoSla;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ConfiguracaoSlaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ConfiguracaoSla');
    }

    public function view(AuthUser $authUser, ConfiguracaoSla $configuracaoSla): bool
    {
        return $authUser->can('View:ConfiguracaoSla');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ConfiguracaoSla');
    }

    public function update(AuthUser $authUser, ConfiguracaoSla $configuracaoSla): bool
    {
        return $authUser->can('Update:ConfiguracaoSla');
    }

    public function delete(AuthUser $authUser, ConfiguracaoSla $configuracaoSla): bool
    {
        return $authUser->can('Delete:ConfiguracaoSla');
    }

    public function restore(AuthUser $authUser, ConfiguracaoSla $configuracaoSla): bool
    {
        return $authUser->can('Restore:ConfiguracaoSla');
    }

    public function forceDelete(AuthUser $authUser, ConfiguracaoSla $configuracaoSla): bool
    {
        return $authUser->can('ForceDelete:ConfiguracaoSla');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ConfiguracaoSla');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ConfiguracaoSla');
    }

    public function replicate(AuthUser $authUser, ConfiguracaoSla $configuracaoSla): bool
    {
        return $authUser->can('Replicate:ConfiguracaoSla');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ConfiguracaoSla');
    }
}
