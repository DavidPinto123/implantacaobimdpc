<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AsFaixaArea;
use Illuminate\Auth\Access\HandlesAuthorization;

class AsFaixaAreaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AsFaixaArea');
    }

    public function view(AuthUser $authUser, AsFaixaArea $asFaixaArea): bool
    {
        return $authUser->can('View:AsFaixaArea');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AsFaixaArea');
    }

    public function update(AuthUser $authUser, AsFaixaArea $asFaixaArea): bool
    {
        return $authUser->can('Update:AsFaixaArea');
    }

    public function delete(AuthUser $authUser, AsFaixaArea $asFaixaArea): bool
    {
        return $authUser->can('Delete:AsFaixaArea');
    }

    public function restore(AuthUser $authUser, AsFaixaArea $asFaixaArea): bool
    {
        return $authUser->can('Restore:AsFaixaArea');
    }

    public function forceDelete(AuthUser $authUser, AsFaixaArea $asFaixaArea): bool
    {
        return $authUser->can('ForceDelete:AsFaixaArea');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AsFaixaArea');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AsFaixaArea');
    }

    public function replicate(AuthUser $authUser, AsFaixaArea $asFaixaArea): bool
    {
        return $authUser->can('Replicate:AsFaixaArea');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AsFaixaArea');
    }

}