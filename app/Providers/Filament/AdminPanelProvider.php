<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->path('vibe-hq')
            ->login()
            // Standard profile page disabled in favor of modal
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Edit Profile')
                    ->icon('heroicon-m-user-circle')
                    ->url("javascript:Livewire.dispatch('open-admin-profile-modal')"),
            ])
            ->renderHook(
                'panels::body.end',
                fn() => \Illuminate\Support\Facades\Blade::render("@livewire('admin.edit-profile-modal')"),
            )
            ->renderHook(
                'panels::head.start',
                fn() => new \Illuminate\Support\HtmlString('<meta name="robots" content="noindex, nofollow">'),
            )
            ->renderHook(
                'panels::head.end',
                fn(): string => '<style>
                    /* Sidebar: White & Clean */
                    .fi-sidebar {
                        background-color: white !important;
                        border-right: 1px solid rgb(243 244 246); /* gray-100 */
                    }
                    .fi-sidebar-header {
                        background-color: white !important;
                    }

                    /* Rounded Buttons & Inputs (XL) */
                    .fi-btn, .fi-input, .fi-select-input, .fi-dropdown-trigger {
                        border-radius: 0.75rem !important; /* rounded-xl */
                    }

                    /* Soften Borders */
                    .fi-card, .fi-section, .fi-widget {
                        border-radius: 1rem !important; /* rounded-2xl */
                        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05) !important;
                        border: 1px solid rgb(241 245 249) !important; /* slate-100 */
                    }

                    /* Table Header: Transparent & Clean */
                    .fi-ta-header-cell {
                        background-color: transparent !important;
                        font-weight: 600 !important;
                    }
                </style>',
            )
            ->brandName('VibeFinance Admin')
            ->favicon(asset('favicon.svg'))
            ->font('Inter')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
                'purple' => Color::Purple,
                'success' => Color::Emerald,
                'info' => Color::Blue,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\IncomeChart::class,
                \App\Filament\Widgets\TransactionChart::class,
                \App\Filament\Widgets\TrafficChart::class,
                \App\Filament\Widgets\EngagementChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
