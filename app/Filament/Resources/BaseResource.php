<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResource extends Resource
{
    protected static function shieldPermissionName(string $prefix): string
    {
        return $prefix . '_' . FilamentShield::getPermissionIdentifier(static::class);
    }

    protected static function shieldCan(?Authenticatable $user, string $prefix): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can(static::shieldPermissionName($prefix));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return parent::shouldRegisterNavigation() && static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return static::shieldCan($user, 'view_any')
            || static::shieldCan($user, 'view_limit');
    }

    public static function canView(Model $record): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'view');
    }

    public static function canCreate(): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'delete_any');
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'force_delete');
    }

    public static function canForceDeleteAny(): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'force_delete_any');
    }

    public static function canRestore(Model $record): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'restore');
    }

    public static function canRestoreAny(): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'restore_any');
    }

    public static function canReplicate(Model $record): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'replicate');
    }

    public static function canReorder(): bool
    {
        return static::shieldCan(Filament::auth()->user(), 'reorder');
    }
}
