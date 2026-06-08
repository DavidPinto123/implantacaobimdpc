<?php

namespace App\Policies;

use App\Models\ControlePedido;
use App\Models\User;

class ControlePedidoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:ControlePedido');
    }

    public function view(User $user, ControlePedido $record): bool
    {
        return $user->can('View:ControlePedido');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:ControlePedido');
    }

    public function update(User $user, ControlePedido $record): bool
    {
        return $user->can('Update:ControlePedido');
    }

    public function delete(User $user, ControlePedido $record): bool
    {
        return $user->can('Delete:ControlePedido');
    }

    public function restore(User $user, ControlePedido $record): bool
    {
        return $user->can('Restore:ControlePedido');
    }

    public function forceDelete(User $user, ControlePedido $record): bool
    {
        return $user->can('ForceDelete:ControlePedido');
    }

    // “Any” (bulk/ações globais) — marque se você usa essas ações
    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:ControlePedido');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:ControlePedido');
    }

    public function replicate(User $user, ControlePedido $record): bool
    {
        return $user->can('Replicate:ControlePedido');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:ControlePedido');
    }
}
