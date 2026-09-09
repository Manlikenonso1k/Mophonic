<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Resource authorisation against granular permissions rather than role names,
 * so a new role is a matter of granting permissions, not editing resources.
 *
 * Filament hides a navigation item when `canViewAny()` is false, which is what
 * keeps a waiter's sidebar free of screens that would only error on click.
 */
trait AuthorizesWithPermissions
{
    /** The permission suffix, e.g. `order` for `view_any_order`. */
    abstract protected static function permissionSubject(): string;

    public static function canViewAny(): bool
    {
        return static::allows('view_any');
    }

    public static function canView(Model $record): bool
    {
        return static::allows('view');
    }

    public static function canCreate(): bool
    {
        return static::allows('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allows('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::allows('delete');
    }

    protected static function allows(string $action, ?string $subject = null): bool
    {
        return Filament::auth()->user()?->can($action.'_'.($subject ?? static::permissionSubject())) ?? false;
    }
}
