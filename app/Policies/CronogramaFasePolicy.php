<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CronogramaFase;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CronogramaFasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CronogramaFase');
    }

    public function view(AuthUser $authUser, CronogramaFase $cronogramaFase): bool
    {
        return $authUser->can('View:CronogramaFase');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CronogramaFase');
    }

    public function update(AuthUser $authUser, CronogramaFase $cronogramaFase): bool
    {
        return $authUser->can('Update:CronogramaFase');
    }

    public function delete(AuthUser $authUser, CronogramaFase $cronogramaFase): bool
    {
        return $authUser->can('Delete:CronogramaFase');
    }

    public function restore(AuthUser $authUser, CronogramaFase $cronogramaFase): bool
    {
        return $authUser->can('Restore:CronogramaFase');
    }

    public function forceDelete(AuthUser $authUser, CronogramaFase $cronogramaFase): bool
    {
        return $authUser->can('ForceDelete:CronogramaFase');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CronogramaFase');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CronogramaFase');
    }

    public function replicate(AuthUser $authUser, CronogramaFase $cronogramaFase): bool
    {
        return $authUser->can('Replicate:CronogramaFase');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CronogramaFase');
    }
}
