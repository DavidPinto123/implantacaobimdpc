<?php

namespace App\Policies;

use App\Models\ObraRecebimento;
use Illuminate\Foundation\Auth\User as AuthUser;

class ObraRecebimentoPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ObraRecebimento');
    }

    public function view(AuthUser $authUser, ObraRecebimento $obraRecebimento): bool
    {
        return $authUser->can('View:ObraRecebimento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ObraRecebimento');
    }

    public function update(AuthUser $authUser, ObraRecebimento $obraRecebimento): bool
    {
        return $authUser->can('Update:ObraRecebimento');
    }

    public function delete(AuthUser $authUser, ObraRecebimento $obraRecebimento): bool
    {
        return $authUser->can('Delete:ObraRecebimento');
    }

    public function restore(AuthUser $authUser, ObraRecebimento $obraRecebimento): bool
    {
        return $authUser->can('Restore:ObraRecebimento');
    }

    public function forceDelete(AuthUser $authUser, ObraRecebimento $obraRecebimento): bool
    {
        return $authUser->can('ForceDelete:ObraRecebimento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ObraRecebimento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ObraRecebimento');
    }

    public function replicate(AuthUser $authUser, ObraRecebimento $obraRecebimento): bool
    {
        return $authUser->can('Replicate:ObraRecebimento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ObraRecebimento');
    }
}
