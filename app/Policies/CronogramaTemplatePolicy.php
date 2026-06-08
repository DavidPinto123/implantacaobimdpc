<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CronogramaTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CronogramaTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CronogramaTemplate');
    }

    public function view(AuthUser $authUser, CronogramaTemplate $cronogramaTemplate): bool
    {
        return $authUser->can('View:CronogramaTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CronogramaTemplate');
    }

    public function update(AuthUser $authUser, CronogramaTemplate $cronogramaTemplate): bool
    {
        return $authUser->can('Update:CronogramaTemplate');
    }

    public function delete(AuthUser $authUser, CronogramaTemplate $cronogramaTemplate): bool
    {
        return $authUser->can('Delete:CronogramaTemplate');
    }

    public function restore(AuthUser $authUser, CronogramaTemplate $cronogramaTemplate): bool
    {
        return $authUser->can('Restore:CronogramaTemplate');
    }

    public function forceDelete(AuthUser $authUser, CronogramaTemplate $cronogramaTemplate): bool
    {
        return $authUser->can('ForceDelete:CronogramaTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CronogramaTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CronogramaTemplate');
    }

    public function replicate(AuthUser $authUser, CronogramaTemplate $cronogramaTemplate): bool
    {
        return $authUser->can('Replicate:CronogramaTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CronogramaTemplate');
    }
}
