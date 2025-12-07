<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Pages\MasterDatas;
use App\Filament\Pages\ChatRoomPage;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Blade;
use Filament\Navigation\NavigationGroup;
use Shanerbaner82\PanelRoles\PanelRoles;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Monzer\FilamentChatifyIntegration\ChatifyPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin as ShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use App\Filament\Widgets\WelcomeWeatherWidget;
use App\Filament\Widgets\AbsensiWidget;
use App\Filament\Widgets\AdvancedStatsOverviewWidget;
use App\Models\Absensi;
use Filament\Widgets\Widget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->registration()
            // ->topbar()
            // ->topNavigation()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->login()
            ->colors([
                'primary' => Color::Blue,
                'secondary' => Color::Green,
                'accent' => Color::Red,
            ])
            // ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // ->widgets([
            //     Widgets\AccountWidget::class,
            //     Widgets\FilamentInfoWidget::class,
            // ])
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
            ->plugin(ShieldPlugin::make())
            ->plugin(ChatifyPlugin::make()->customPage(ChatRoomPage::class)->disableFloatingChatWidget())
            ->plugin(
                \TomatoPHP\FilamentMediaManager\FilamentMediaManagerPlugin::make()
                    ->allowSubFolders()
                    
            )

        ->plugin(
                PanelRoles::make()
                    ->roleToAssign('super_admin')
                    ->restrictedRoles(['super_admin', 'kasir', 'petugas']),
            )
            ->navigationGroups([
                NavigationGroup::make('Master Data'),
                NavigationGroup::make('Inventory'),
                NavigationGroup::make('Absensi'),
                NavigationGroup::make('Penjadwalan'),
                NavigationGroup::make('Pengaturan'),
                NavigationGroup::make('Reports'),
                NavigationGroup::make('Content'),
                
            ])
            ->widgets([
                // Kita daftarkan widget kita di sini, pastikan ada di posisi pertama
                WelcomeWeatherWidget::class, 
                AdvancedStatsOverviewWidget::class,
                // \App\Filament\Widgets\AccountOverview::class, // Widget default Filament (jika ada)
                // \App\Filament\Widgets\FilamentInfoWidget::class, // Widget default Filament (jika ada)
                AbsensiWidget::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => <<<HTML
                    <style>
                        /* --- 0. SIDEBAR SETTINGS --- */
                        :root {
                            --sidebar-width: 13rem;
                        }

                        /* --- 1. SIDEBAR BACKGROUND & BORDER --- */
                        .fi-sidebar {
                            background-color: #f0f0f0 !important;
                            border-right: 1px solid #e5e5e5 !important;
                            border-left: none !important;
                            border-bottom: none !important;
                        }
                        .fi-sidebar-header {
                            background-color: #f0f0f0 !important;
                            border-bottom: none !important;
                        }

                        /* Dark Mode Background */
                        html.dark .fi-sidebar {
                            background-color: #131313 !important;
                            border-right: 1px solid #262626 !important;
                        }
                        html.dark .fi-sidebar-header {
                            background-color: #131313 !important;
                        }

                        /* --- 2. TYPOGRAPHY & COLORS --- */
                        .fi-sidebar-nav { gap: 0px !important; }
                        .fi-sidebar-group-items { gap: 1px !important; }
                        .fi-sidebar-sub-group-items { gap: 0px !important; }
                        .fi-sidebar-nav-groups.gap-y-7 { row-gap: 0px !important; }

                        /* A. ITEM MENU (Button Base) */
                        .fi-sidebar-item-button {
                            padding-block: 5px !important;
                            padding-inline: 10px !important;
                            margin-inline: 8px !important;
                            border-radius: 6px !important;
                            font-size: 0.825rem !important;
                            font-weight: 500 !important;
                            color: #525252 !important; /* Neutral-600 */
                            transition: all 0.1s ease-in-out !important; /* Dipercepat agar :active terasa */
                        }
                        html.dark .fi-sidebar-item-button {
                            color: #a3a3a3 !important; /* Neutral-400 */
                        }

                        /* B. ICON STYLE */
                        .fi-sidebar-item-icon {
                            width: 1.1rem !important;
                            height: 1.1rem !important;
                            opacity: 0.7 !important;
                            color: currentColor !important;
                            transition: opacity 0.2s ease;
                        }

                        /* C. HOVER STATE (General) */
                        .fi-sidebar-item-button:hover {
                            background-color: rgba(0, 0, 0, 0.04) !important;
                            color: #171717 !important;
                        }
                        .fi-sidebar-item-button:hover .fi-sidebar-item-icon {
                            opacity: 1 !important;
                        }
                        html.dark .fi-sidebar-item-button:hover {
                            background-color: rgba(255, 255, 255, 0.05) !important;
                            color: #f5f5f5 !important;
                        }

                        /* --- D. ACTIVE STATE (ACCENT & CLICK FEEDBACK) --- */
                        
                        /* D1. Background saat Aktif (Page) ATAU Diklik (:active) */
                        .fi-sidebar-item.fi-active .fi-sidebar-item-button,
                        .fi-sidebar-item-button:active {
                            background-color: rgba(0, 0, 0, 0.06) !important;
                        }
                        html.dark .fi-sidebar-item.fi-active .fi-sidebar-item-button,
                        html.dark .fi-sidebar-item-button:active {
                            background-color: rgba(255, 255, 255, 0.08) !important;
                        }

                        /* D2. Text Label & Icon Color saat Aktif (Page) ATAU Diklik (:active) */
                        /* LIGHT MODE: Gunakan Primary-600 */
                        .fi-sidebar-item.fi-active .fi-sidebar-item-label,
                        .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
                        .fi-sidebar-item-button:active .fi-sidebar-item-label,
                        .fi-sidebar-item-button:active .fi-sidebar-item-icon {
                            color: rgb(var(--primary-600)) !important; 
                            font-weight: 600 !important;
                            opacity: 1 !important;
                        }

                        /* DARK MODE: Gunakan Primary-400 */
                        html.dark .fi-sidebar-item.fi-active .fi-sidebar-item-label,
                        html.dark .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
                        html.dark .fi-sidebar-item-button:active .fi-sidebar-item-label,
                        html.dark .fi-sidebar-item-button:active .fi-sidebar-item-icon {
                            color: rgb(var(--primary-400)) !important;
                            font-weight: 600 !important;
                        }


                        /* --- E. GROUP HEADER (Adjusted) --- */
                        .fi-sidebar-group-button {
                            padding-block: 6px !important;
                            padding-inline: 10px !important;
                            margin-inline: 8px !important;
                            margin-top: 6px !important;
                            border-radius: 6px !important;
                        }
                        
                        /* Hover & Active Group */
                        .fi-sidebar-group-button:hover,
                        .fi-sidebar-group-button:active {
                            background-color: rgba(0, 0, 0, 0.04) !important;
                        }
                        
                        /* Dark Mode Group Hover/Active */
                        html.dark .fi-sidebar-group-button:hover,
                        html.dark .fi-sidebar-group-button:active {
                            background-color: rgba(255, 255, 255, 0.05) !important;
                        }

                        /* Label Base */
                        .fi-sidebar-group-label {
                            padding: 0 !important;
                            font-size: 0.7rem !important;
                            text-transform: uppercase;
                            letter-spacing: 0.08em;
                            font-weight: 600;
                            color: #737373 !important;
                        }
                        
                        /* Group Label saat diklik (:active) */
                        .fi-sidebar-group-button:active .fi-sidebar-group-label {
                            color: rgb(var(--primary-600)) !important;
                        }
                        html.dark .fi-sidebar-group-button:active .fi-sidebar-group-label {
                            color: rgb(var(--primary-400)) !important;
                        }

                        /* --- F. PARENT ACTIVE LOGIC (:has) - COMPLETE FIX --- */
            
                        /* =========================================
                           CASE 1: GROUP HEADER (navigationGroup) 
                           ========================================= */
                        
                        /* Background Group */
                        .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-button {
                            background-color: rgba(0, 0, 0, 0.04) !important;
                        }
                        html.dark .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-button {
                            background-color: rgba(255, 255, 255, 0.05) !important;
                        }

                        /* Label & Icon Group (Accent) */
                        .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-label,
                        .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-icon, 
                        .fi-sidebar-group:has(.fi-sidebar-item.fi-active) button span { 
                            color: rgb(var(--primary-600)) !important;
                            font-weight: 600 !important;
                            opacity: 1 !important;
                        }
                        /* Dark Mode */
                        html.dark .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-label,
                        html.dark .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-icon,
                        html.dark .fi-sidebar-group:has(.fi-sidebar-item.fi-active) button span { 
                            color: rgb(var(--primary-400)) !important;
                            font-weight: 600 !important;
                            opacity: 1 !important;
                        }

                        /* =========================================
                           CASE 2: PARENT ITEM (navigationParentItem)
                           [INI YANG SEBELUMNYA HILANG/KURANG]
                           ========================================= */

                        /* 1. Background Parent Item saat Child Aktif */
                        .fi-sidebar-item:has(.fi-sidebar-item.fi-active) > .fi-sidebar-item-button {
                            background-color: rgba(0, 0, 0, 0.04) !important;
                        }
                        html.dark .fi-sidebar-item:has(.fi-sidebar-item.fi-active) > .fi-sidebar-item-button {
                            background-color: rgba(255, 255, 255, 0.05) !important;
                        }

                        /* 2. Text Label & Icon Parent Item saat Child Aktif */
                        .fi-sidebar-item:has(.fi-sidebar-item.fi-active) > .fi-sidebar-item-button .fi-sidebar-item-label,
                        .fi-sidebar-item:has(.fi-sidebar-item.fi-active) > .fi-sidebar-item-button .fi-sidebar-item-icon {
                            color: rgb(var(--primary-600)) !important;
                            font-weight: 600 !important;
                            opacity: 1 !important;
                        }

                        /* Dark Mode Fix */
                        html.dark .fi-sidebar-item:has(.fi-sidebar-item.fi-active) > .fi-sidebar-item-button .fi-sidebar-item-label,
                        html.dark .fi-sidebar-item:has(.fi-sidebar-item.fi-active) > .fi-sidebar-item-button .fi-sidebar-item-icon {
                            color: rgb(var(--primary-400)) !important; /* Accent menyala di dark mode */
                            font-weight: 600 !important;
                            opacity: 1 !important;
                        }

                        /* --- G. ICON BUTTONS (.fi-icon-btn) --- */
                        .fi-sidebar .fi-icon-btn {
                            transition: color 0.2s ease;
                        }
                        .fi-sidebar .fi-icon-btn:hover,
                        .fi-sidebar .fi-icon-btn:focus,
                        .fi-sidebar .fi-icon-btn:active {
                            color: rgb(var(--primary-600)) !important;
                            background-color: rgba(0,0,0,0.05);
                        }
                        html.dark .fi-sidebar .fi-icon-btn:hover,
                        html.dark .fi-sidebar .fi-icon-btn:focus,
                        html.dark .fi-sidebar .fi-icon-btn:active {
                            color: rgb(var(--primary-400)) !important;
                            background-color: rgba(255,255,255,0.05);
                        }

                        /* Utilities & Scrollbar */
                        .fi-sidebar-group { border-top: none !important; }
                        .gap-y-1 { row-gap: 1px !important; }
                        .gap-y-2 { row-gap: 2px !important; }
                        
                        .fi-sidebar-nav {
                            scrollbar-width: thin;
                            scrollbar-color: transparent transparent;
                            transition: scrollbar-color 0.3s ease;
                        }
                        .fi-sidebar-nav:hover {
                            scrollbar-color: rgba(128, 128, 128, 0.2) transparent;
                        }
                    </style>
                HTML
            )

            ->renderHook(
                'panels::body.end',
                fn () => view('filament.hooks.absensi-geolocation-script')
            
            )
            
            // --- 5. DRAGGABLE SIDEBAR LOGIC (NEW) ---
            ->renderHook(
                'panels::body.end',
                fn (): string => \Illuminate\Support\Facades\Blade::render(<<<'HTML'
                    <div id="sidebar-resizer" class="hidden md:block"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const resizer = document.getElementById('sidebar-resizer');
                            const sidebar = document.querySelector('.fi-sidebar');
                            
                            // Ambil ukuran tersimpan atau default 13rem
                            let sidebarWidth = localStorage.getItem('filament-sidebar-width') || '13rem';
                            
                            // Set ukuran awal
                            document.documentElement.style.setProperty('--sidebar-width', sidebarWidth);

                            function initResize(e) {
                                e.preventDefault(); // Mencegah seleksi teks saat drag
                                window.addEventListener('mousemove', resize);
                                window.addEventListener('mouseup', stopResize);
                                document.body.style.cursor = 'col-resize'; // Ubah kursor global
                                document.body.classList.add('resizing'); // Opsional: untuk styling saat resizing
                            }

                            function resize(e) {
                                // Batasan min/max (misal min 200px, max 600px)
                                if (e.pageX < 200 || e.pageX > 600) return;
                                
                                const newWidth = e.pageX + 'px';
                                document.documentElement.style.setProperty('--sidebar-width', newWidth);
                                localStorage.setItem('filament-sidebar-width', newWidth);
                            }

                            function stopResize() {
                                window.removeEventListener('mousemove', resize);
                                window.removeEventListener('mouseup', stopResize);
                                document.body.style.cursor = '';
                                document.body.classList.remove('resizing');
                            }

                            resizer.addEventListener('mousedown', initResize);
                        });
                    </script>
                    <style>
                        /* Handle Resizer */
                        #sidebar-resizer {
                            width: 6px; /* Area grab mouse */
                            background: transparent;
                            position: fixed;
                            z-index: 49; /* Di atas konten, di bawah modal */
                            top: 0;
                            bottom: 0;
                            left: var(--sidebar-width); /* Selalu mengikuti lebar sidebar */
                            cursor: col-resize;
                            transition: background 0.2s;
                        }

                        /* Hover effect pada handle agar user tahu bisa digeser */
                        #sidebar-resizer:hover, 
                        body.resizing #sidebar-resizer {
                            background: rgba(var(--primary-500), 0.5); /* Muncul garis biru samar saat hover/drag */
                            /* Atau warna netral: background: rgba(128, 128, 128, 0.2); */
                        }

                        /* Mencegah seleksi teks aneh saat dragging */
                        body.resizing {
                            user-select: none;
                        }
                    </style>
                HTML)
            );
            
            
    }
}
