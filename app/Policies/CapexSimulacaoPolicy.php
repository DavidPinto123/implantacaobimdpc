<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CapexSimulacao;
use Illuminate\Auth\Access\HandlesAuthorization;

class CapexSimulacaoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CapexSimulacao');
    }

    public function view(AuthUser $authUser, CapexSimulacao $capexSimulacao): bool
    {
        return $authUser->can('View:CapexSimulacao');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CapexSimulacao');
    }

    public function update(AuthUser $authUser, CapexSimulacao $capexSimulacao): bool
    {
        return $authUser->can('Update:CapexSimulacao');
    }

    public function delete(AuthUser $authUser, CapexSimulacao $capexSimulacao): bool
    {
        return $authUser->can('Delete:CapexSimulacao');
    }

    public function restore(AuthUser $authUser, CapexSimulacao $capexSimulacao): bool
    {
        return $authUser->can('Restore:CapexSimulacao');
    }

    public function forceDelete(AuthUser $authUser, CapexSimulacao $capexSimulacao): bool
    {
        return $authUser->can('ForceDelete:CapexSimulacao');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CapexSimulacao');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CapexSimulacao');
    }

    public function replicate(AuthUser $authUser, CapexSimulacao $capexSimulacao): bool
    {
        return $authUser->can('Replicate:CapexSimulacao');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CapexSimulacao');
    }

}