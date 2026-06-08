<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pipe;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PipePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pipe');
    }

    public function view(AuthUser $authUser, Pipe $pipe): bool
    {
        return $authUser->can('View:Pipe');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pipe');
    }

    public function update(AuthUser $authUser, Pipe $pipe): bool
    {
        return $authUser->can('Update:Pipe');
    }

    public function delete(AuthUser $authUser, Pipe $pipe): bool
    {
        return $authUser->can('Delete:Pipe');
    }

    public function restore(AuthUser $authUser, Pipe $pipe): bool
    {
        return $authUser->can('Restore:Pipe');
    }

    public function forceDelete(AuthUser $authUser, Pipe $pipe): bool
    {
        return $authUser->can('ForceDelete:Pipe');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pipe');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pipe');
    }

    public function replicate(AuthUser $authUser, Pipe $pipe): bool
    {
        return $authUser->can('Replicate:Pipe');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pipe');
    }
}
