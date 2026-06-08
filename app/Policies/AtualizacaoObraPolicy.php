<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AtualizacaoObra;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AtualizacaoObraPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AtualizacaoObra');
    }

    public function view(AuthUser $authUser, AtualizacaoObra $atualizacao): bool
    {
        return $authUser->can('View:AtualizacaoObra');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AtualizacaoObra');
    }

    public function update(AuthUser $authUser, AtualizacaoObra $atualizacao): bool
    {
        if ($authUser->can('Update:AtualizacaoObra')) {
            return true;
        }

        return $atualizacao->usuario_id === $authUser->id;
    }

    public function delete(AuthUser $authUser, AtualizacaoObra $atualizacao): bool
    {
        if ($authUser->can('Delete:AtualizacaoObra')) {
            return true;
        }

        return $atualizacao->usuario_id === $authUser->id;
    }
}
