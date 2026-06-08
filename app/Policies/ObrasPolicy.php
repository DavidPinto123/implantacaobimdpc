<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Obras;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ObrasPolicy
{
    use HandlesAuthorization;

    private function isConstrutora(AuthUser $authUser): bool
    {
        return method_exists($authUser, 'hasRole') && $authUser->hasRole('Fornecedor');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('ViewAny:Obras');
    }

    public function view(AuthUser $authUser, Obras $obras): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('View:Obras');
    }

    public function create(AuthUser $authUser): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('Create:Obras');
    }

    public function update(AuthUser $authUser, Obras $obras): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('Update:Obras');
    }

    public function delete(AuthUser $authUser, Obras $obras): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('Delete:Obras');
    }

    public function restore(AuthUser $authUser, Obras $obras): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('Restore:Obras');
    }

    public function forceDelete(AuthUser $authUser, Obras $obras): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('ForceDelete:Obras');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('ForceDeleteAny:Obras');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('RestoreAny:Obras');
    }

    public function replicate(AuthUser $authUser, Obras $obras): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('Replicate:Obras');
    }

    public function reorder(AuthUser $authUser): bool
    {
        if ($this->isConstrutora($authUser)) {
            return false;
        }

        return $authUser->can('Reorder:Obras');
    }
}
