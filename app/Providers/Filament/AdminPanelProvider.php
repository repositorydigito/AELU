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
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;
use Solutionforest\FilamentLoginScreen\Filament\Pages\Auth\Themes\Theme1\LoginScreenPage;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use TomatoPHP\FilamentUsers\FilamentUsersPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Support\Enums\MaxWidth;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(LoginScreenPage::class)
            ->darkMode(false)
            ->brandName('AELU')
            ->brandLogo(asset('images/logoAELU.svg'))
            ->sidebarFullyCollapsibleOnDesktop()
            ->colors([
                'primary' => '#017D47',
                'gray' => Color::Slate,
                'info' => Color::Cyan,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->font('Inter')
            ->maxContentWidth(MaxWidth::ScreenTwoExtraLarge)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->plugins([
                FilamentUsersPlugin::make(),
                FilamentShieldPlugin::make(),
                FilamentApexChartsPlugin::make(),
                FilamentEditProfilePlugin::make()
                    ->setTitle('Editar Perfil')        
                    ->setNavigationLabel('Perfil')    
                    ->setIcon('heroicon-o-user-circle')
                    ->shouldRegisterNavigation() 
            ])
            ->sidebarWidth('18rem')
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Gestión de Embarcaciones')
                    ->icon('heroicon-o-globe-alt'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Configuración')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
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
            ])
            ->renderHook('panels::body.start', fn()=>'
                <style>
                    .fi-section-content-ctn{
                        background-color:rgb(255, 255, 253) !important;
                    }

                    .fi-sidebar{
                        background-color:white !important;
                    }

                    .fi-sidebar-item-label{
                        color:#404040 !important;
                    }

                    .fi-sidebar-item-icon {
                        color: #017D47 !important;
                    }
                    
                    .fi-sidebar-group-label{
                        color:grey;
                    }

                    .fi-sidebar-item-active .fi-sidebar-item-label{
                        color: #017D47 !important;
                    }

                    .fi-sidebar-item-active .fi-sidebar-item-icon {
                        color: #017D47 !important;
                    }
                    
                    .fi-sidebar-item a>span:hover{
                        color: #017D47 !important;
                    }

                    .fi-sidebar-item-active a {
                        background-color: #E6F2ED !important;
                        border-radius: 0.5rem;
                    }
                </style>
            ');
    }
}
