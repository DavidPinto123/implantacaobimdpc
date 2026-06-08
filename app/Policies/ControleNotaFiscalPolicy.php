<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ControleNotaFiscal;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ControleNotaFiscalPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ControleNotaFiscal');
    }

    public function view(AuthUser $authUser, ControleNotaFiscal $controleNotaFiscal): bool
    {
        return $authUser->can('View:ControleNotaFiscal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ControleNotaFiscal');
    }

    public function update(AuthUser $authUser, ControleNotaFiscal $controleNotaFiscal): bool
    {
        return $authUser->can('Update:ControleNotaFiscal');
    }

    public function delete(AuthUser $authUser, ControleNotaFiscal $controleNotaFiscal): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, ControleNotaFiscal $controleNotaFiscal): bool
    {
        return $authUser->can('Restore:ControleNotaFiscal');
    }

    public function forceDelete(AuthUser $authUser, ControleNotaFiscal $controleNotaFiscal): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ControleNotaFiscal');
    }

    public function replicate(AuthUser $authUser, ControleNotaFiscal $controleNotaFiscal): bool
    {
        return $authUser->can('Replicate:ControleNotaFiscal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ControleNotaFiscal');
    }
}
