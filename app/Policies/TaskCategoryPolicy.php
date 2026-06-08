<?php

namespace App\Policies;

use App\Models\TaskCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TaskCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TaskCategory');
    }

    public function view(AuthUser $authUser, TaskCategory $taskCategory): bool
    {
        return $authUser->can('View:TaskCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TaskCategory');
    }

    public function update(AuthUser $authUser, TaskCategory $taskCategory): bool
    {
        return $authUser->can('Update:TaskCategory');
    }

    public function delete(AuthUser $authUser, TaskCategory $taskCategory): bool
    {
        return $authUser->can('Delete:TaskCategory');
    }

    public function restore(AuthUser $authUser, TaskCategory $taskCategory): bool
    {
        return $authUser->can('Restore:TaskCategory');
    }

    public function forceDelete(AuthUser $authUser, TaskCategory $taskCategory): bool
    {
        return $authUser->can('ForceDelete:TaskCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TaskCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TaskCategory');
    }

    public function replicate(AuthUser $authUser, TaskCategory $taskCategory): bool
    {
        return $authUser->can('Replicate:TaskCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TaskCategory');
    }
}
