<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Only the heading is overridden. The Livewire form, validation, rate limiting
 * and session handling stay exactly as Filament shipped them — the styling is
 * injected from outside through render hooks (see AdminPanelProvider::boot).
 */
class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return '';
    }
}
