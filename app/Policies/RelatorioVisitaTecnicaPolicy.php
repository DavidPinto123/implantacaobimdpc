<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RelatorioVisitaTecnica;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RelatorioVisitaTecnicaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RelatorioVisitaTecnica');
    }

    public function view(AuthUser $authUser, RelatorioVisitaTecnica $relatorioVisitaTecnica): bool
    {
        return $authUser->can('View:RelatorioVisitaTecnica');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RelatorioVisitaTecnica');
    }

    public function update(AuthUser $authUser, RelatorioVisitaTecnica $relatorioVisitaTecnica): bool
    {
        return $authUser->can('Update:RelatorioVisitaTecnica');
    }

    public function delete(AuthUser $authUser, RelatorioVisitaTecnica $relatorioVisitaTecnica): bool
    {
        return $authUser->can('Delete:RelatorioVisitaTecnica');
    }

    public function restore(AuthUser $authUser, RelatorioVisitaTecnica $relatorioVisitaTecnica): bool
    {
        return $authUser->can('Restore:RelatorioVisitaTecnica');
    }

    public function forceDelete(AuthUser $authUser, RelatorioVisitaTecnica $relatorioVisitaTecnica): bool
    {
        return $authUser->can('ForceDelete:RelatorioVisitaTecnica');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RelatorioVisitaTecnica');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RelatorioVisitaTecnica');
    }

    public function replicate(AuthUser $authUser, RelatorioVisitaTecnica $relatorioVisitaTecnica): bool
    {
        return $authUser->can('Replicate:RelatorioVisitaTecnica');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RelatorioVisitaTecnica');
    }
}
