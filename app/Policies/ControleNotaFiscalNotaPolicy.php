<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ControleNotaFiscalNota;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ControleNotaFiscalNotaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ControleNotaFiscalNota');
    }

    public function view(AuthUser $authUser, ControleNotaFiscalNota $controleNotaFiscalNota): bool
    {
        return $authUser->can('View:ControleNotaFiscalNota');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ControleNotaFiscalNota');
    }

    public function update(AuthUser $authUser, ControleNotaFiscalNota $controleNotaFiscalNota): bool
    {
        return $authUser->can('Update:ControleNotaFiscalNota');
    }

    public function delete(AuthUser $authUser, ControleNotaFiscalNota $controleNotaFiscalNota): bool
    {
        return $authUser->can('Delete:ControleNotaFiscalNota');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ControleNotaFiscalNota');
    }

    public function forceDelete(AuthUser $authUser, ControleNotaFiscalNota $controleNotaFiscalNota): bool
    {
        return $authUser->can('ForceDelete:ControleNotaFiscalNota');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ControleNotaFiscalNota');
    }

    public function restore(AuthUser $authUser, ControleNotaFiscalNota $controleNotaFiscalNota): bool
    {
        return $authUser->can('Restore:ControleNotaFiscalNota');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ControleNotaFiscalNota');
    }

    public function replicate(AuthUser $authUser, ControleNotaFiscalNota $controleNotaFiscalNota): bool
    {
        return $authUser->can('Replicate:ControleNotaFiscalNota');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ControleNotaFiscalNota');
    }
}
