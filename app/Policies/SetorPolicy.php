<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SetorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Setor');
    }

    public function view(AuthUser $authUser, Setor $setor): bool
    {
        return $authUser->can('View:Setor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Setor');
    }

    public function update(AuthUser $authUser, Setor $setor): bool
    {
        return $authUser->can('Update:Setor');
    }

    public function delete(AuthUser $authUser, Setor $setor): bool
    {
        return $authUser->can('Delete:Setor');
    }

    public function restore(AuthUser $authUser, Setor $setor): bool
    {
        return $authUser->can('Restore:Setor');
    }

    public function forceDelete(AuthUser $authUser, Setor $setor): bool
    {
        return $authUser->can('ForceDelete:Setor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Setor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Setor');
    }

    public function replicate(AuthUser $authUser, Setor $setor): bool
    {
        return $authUser->can('Replicate:Setor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Setor');
    }
}
