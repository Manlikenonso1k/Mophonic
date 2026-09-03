<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\OrdersByStatus;
use App\Filament\Widgets\RecentOrders;
use App\Filament\Widgets\SalesOverview;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Mophonik')
            ->login(Login::class)
            // Filament's default 20rem is the whole viewport on a 320px screen,
            // which buries its own close-overlay and traps the menu open. The
            // width is expressed as CSS so a strip of the page always shows.
            ->sidebarWidth('min(20rem, calc(100vw - 3.25rem))')
            ->defaultThemeMode(ThemeMode::Dark)
            ->colors([
                'primary' => '#d1552e',
                'gray' => Color::Zinc,
            ])
            ->navigationGroups([
                'Shop',
                'Site',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                OrdersByStatus::class,
                SalesOverview::class,
                RecentOrders::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Styling is injected from outside rather than by overriding Filament's
     * views: the theme is panel-wide, the brand and footer are scoped to the
     * login page so they cannot leak into the rest of the admin.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('filament.theme')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_START,
            fn (): string => view('filament.sidebar-close')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIMPLE_PAGE_START,
            fn (): string => view('filament.auth.brand')->render(),
            scopes: Login::class,
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIMPLE_PAGE_END,
            fn (): string => view('filament.auth.foot')->render(),
            scopes: Login::class,
        );
    }
}
