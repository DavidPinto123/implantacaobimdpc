<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole as ShieldEditRole;

class EditRole extends ShieldEditRole
{
    protected static string $resource = RoleResource::class;
}
