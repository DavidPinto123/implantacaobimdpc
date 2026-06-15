<?php

namespace App\Traits;

trait HasMenuPermission
{
    protected static function menuPermission(): string
    {
        return '';
    }

    public static function canAccess(): bool
    {
        $perm = static::menuPermission();
        if (empty($perm)) {
            return parent::canAccess();
        }
        return auth()->user()?->can($perm) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $perm = static::menuPermission();
        if (empty($perm)) {
            return parent::shouldRegisterNavigation();
        }
        return auth()->user()?->can($perm) ?? false;
    }
}
