<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AppDashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin as ShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
// use App\Filament\Pages\ChatRoomPage;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
// use Monzer\FilamentChatifyIntegration\ChatifyPlugin;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Haen Komputer')
            ->registration()
            // ->brandLogoUrl('/images/logo.png')
            // ->topbar()
            // ->topNavigation()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->login()
            ->globalSearch(\App\Filament\GlobalSearch\NavigationGlobalSearchProvider::class)
            ->colors([
                'primary' => Color::Blue,
                'secondary' => Color::Green,
                'accent' => Color::Red,
            ])
            // ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                AppDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\OpenWeatherWidget::class,
                \App\Filament\Widgets\WelcomeWeatherWidget::class,
                \App\Filament\Widgets\AbsensiWidget::class,
                \App\Filament\Widgets\ActiveMembersTable::class,
                \App\Filament\Widgets\JadwalKalenderWidget::class,
                \App\Filament\Widgets\AdvancedStatsOverviewWidget::class,
                \App\Filament\Widgets\LowStockProductsTable::class,
                \App\Filament\Widgets\MonthlyRevenueTrendChart::class,
                // \App\Filament\Widgets\PosSalesStatsOverview::class,
                \App\Filament\Widgets\RecentPosTransactionsTable::class,
                \App\Filament\Widgets\ServiceWidget::class,
                \App\Filament\Widgets\TopSellingProductsTable::class,
                \App\Filament\Widgets\TugasWidget::class,
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
                \App\Http\Middleware\SimpleFilamentAuth::class,
            ])
            ->plugin(ShieldPlugin::make())
            // ->plugin(ChatifyPlugin::make()->customPage(ChatRoomPage::class)->disableFloatingChatWidget())
            ->plugin(
                \TomatoPHP\FilamentMediaManager\FilamentMediaManagerPlugin::make()
                    ->allowSubFolders()
            )
            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('My Profile')
                    ->setNavigationLabel('My Profile')
                    ->setNavigationGroup('Group Profile')
                    ->setIcon('heroicon-o-user')
                    ->setSort(10)
                    ->shouldRegisterNavigation(false)
                    ->shouldShowEmailForm()
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowBrowserSessionsForm()
                    ->shouldShowAvatarForm(
                        value: true,
                        directory: 'karyawan/foto',
                        rules: 'mimes:jpeg,png,webp|max:2048'
                    ),
            ])
            ->userMenuItems([
                MenuItem::make('My Profile')
                    ->label('My Profile')
                    ->url(fn(): string => EditProfilePage::getUrl())
                    ->icon('heroicon-o-user'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Tugas')->collapsed(),
                NavigationGroup::make('Master Data')->collapsed(),
                NavigationGroup::make('Transaksi')->collapsed(),
                NavigationGroup::make('Inventori')->collapsed(),
                NavigationGroup::make('Personalia')->collapsed(),
                NavigationGroup::make('Laporan')->collapsed(),
                NavigationGroup::make('Pengaturan')->collapsed(),
            ])
            ->widgets([\SolutionForest\TabLayoutPlugin\Widgets\TabsWidget::class])
            ->renderHook(
                'panels::head.end',
                fn(): string => <<<'HTML'
                    <style>
                        /* --- 0. SIDEBAR SETTINGS --- */
                        :root {
                            --sidebar-width: 20rem;
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
                        .fi-sidebar-item.fi-active > .fi-sidebar-item-button,
                        .fi-sidebar-item-button:active {
                            background-color: rgba(0, 0, 0, 0.06) !important;
                        }
                        html.dark .fi-sidebar-item.fi-active > .fi-sidebar-item-button,
                        html.dark .fi-sidebar-item-button:active {
                            background-color: rgba(255, 255, 255, 0.08) !important;
                        }

                        /* D2. Text Label & Icon Color saat Aktif (Page) ATAU Diklik (:active) */
                        /* LIGHT MODE: Gunakan Primary-600 */
                        .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-label,
                        .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-icon,
                        .fi-sidebar-item-button:active .fi-sidebar-item-label,
                        .fi-sidebar-item-button:active .fi-sidebar-item-icon {
                            color: rgb(var(--primary-600)) !important; 
                            font-weight: 600 !important;
                            opacity: 1 !important;
                        }

                        /* DARK MODE: Gunakan Primary-400 */
                        html.dark .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-label,
                        html.dark .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-icon,
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

                        /* --- H. LABA RUGI TABLE ROW BORDER (DARK MODE) --- */
                        html.dark .lr-row {
                            /* border-bottom-color: rgb(55 65 81) !important; */
                            border-bottom-color: rgba(var(--gray-800), 1) !important;
                        }

                        html.dark .lr-table tbody > tr + tr {
                            /* border-top-color: rgb(55 65 81) !important; gray-700 */
                            border-top-color: rgba(var(--gray-800), 1) !important;
                        }

                        html.dark .lr-table thead th {
                            /* border-bottom-color: rgb(55 65 81) !important; gray-700 */
                            border-bottom-color: rgba(var(--gray-800), 1) !important;
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

                        /* --- SOFTER ACTION BUTTON COLORS (Matching Badge Colors) --- */
                        
                        /* Info/Primary buttons - Soft blue (matches badge info) */
                        .fi-btn.fi-color-info.bg-custom-600,
                        .fi-btn.fi-btn-color-info.bg-custom-600,
                        .fi-btn.fi-color-primary.bg-custom-600,
                        .fi-btn.fi-btn-color-primary.bg-custom-600 {
                            background-color: rgb(239 246 255) !important;
                            color: rgb(37 99 235) !important;
                            border: 1px solid rgb(191 219 254) !important;
                        }
                        .fi-btn.fi-color-info.bg-custom-600:hover,
                        .fi-btn.fi-btn-color-info.bg-custom-600:hover,
                        .fi-btn.fi-color-primary.bg-custom-600:hover,
                        .fi-btn.fi-btn-color-primary.bg-custom-600:hover {
                            background-color: rgb(219 234 254) !important;
                            border-color: rgb(147 197 253) !important;
                        }
                        .fi-btn.fi-color-info.bg-custom-600 svg,
                        .fi-btn.fi-btn-color-info.bg-custom-600 svg,
                        .fi-btn.fi-color-primary.bg-custom-600 svg,
                        .fi-btn.fi-btn-color-primary.bg-custom-600 svg {
                            color: rgb(37 99 235) !important;
                        }

                        /* Success buttons - Soft green (matches badge success) */
                        .fi-btn.fi-color-success.bg-custom-600,
                        .fi-btn.fi-btn-color-success.bg-custom-600 {
                            background-color: rgb(240 253 244) !important;
                            color: rgb(22 163 74) !important;
                            border: 1px solid rgb(187 247 208) !important;
                        }
                        .fi-btn.fi-color-success.bg-custom-600:hover,
                        .fi-btn.fi-btn-color-success.bg-custom-600:hover {
                            background-color: rgb(220 252 231) !important;
                            border-color: rgb(134 239 172) !important;
                        }
                        .fi-btn.fi-color-success.bg-custom-600 svg,
                        .fi-btn.fi-btn-color-success.bg-custom-600 svg {
                            color: rgb(22 163 74) !important;
                        }

                        /* Danger buttons - Soft red (matches badge danger) */
                        .fi-btn.fi-color-danger.bg-custom-600,
                        .fi-btn.fi-btn-color-danger.bg-custom-600 {
                            background-color: rgb(254 242 242) !important;
                            color: rgb(220 38 38) !important;
                            border: 1px solid rgb(254 202 202) !important;
                        }
                        .fi-btn.fi-color-danger.bg-custom-600:hover,
                        .fi-btn.fi-btn-color-danger.bg-custom-600:hover {
                            background-color: rgb(254 226 226) !important;
                            border-color: rgb(252 165 165) !important;
                        }
                        .fi-btn.fi-color-danger.bg-custom-600 svg,
                        .fi-btn.fi-btn-color-danger.bg-custom-600 svg {
                            color: rgb(220 38 38) !important;
                        }

                        /* Warning buttons - Soft amber (matches badge warning) */
                        .fi-btn.fi-color-warning.bg-custom-600,
                        .fi-btn.fi-btn-color-warning.bg-custom-600 {
                            background-color: rgb(255 251 235) !important;
                            color: rgb(217 119 6) !important;
                            border: 1px solid rgb(253 230 138) !important;
                        }
                        .fi-btn.fi-color-warning.bg-custom-600:hover,
                        .fi-btn.fi-btn-color-warning.bg-custom-600:hover {
                            background-color: rgb(254 243 199) !important;
                            border-color: rgb(252 211 77) !important;
                        }
                        .fi-btn.fi-color-warning.bg-custom-600 svg,
                        .fi-btn.fi-btn-color-warning.bg-custom-600 svg {
                            color: rgb(217 119 6) !important;
                        }

                        /* Gray buttons - Soft gray (matches badge gray) */
                        .fi-btn.fi-color-gray.bg-custom-600,
                        .fi-btn.fi-btn-color-gray.bg-custom-600 {
                            background-color: rgb(250 250 250) !important;
                            color: rgb(82 82 91) !important;
                            border: 1px solid rgb(228 228 231) !important;
                        }
                        .fi-btn.fi-color-gray.bg-custom-600:hover,
                        .fi-btn.fi-btn-color-gray.bg-custom-600:hover {
                            background-color: rgb(244 244 245) !important;
                            border-color: rgb(212 212 216) !important;
                        }
                        .fi-btn.fi-color-gray.bg-custom-600 svg,
                        .fi-btn.fi-btn-color-gray.bg-custom-600 svg {
                            color: rgb(82 82 91) !important;
                        }

                        /* ============================================= */
                        /* DARK MODE BUTTON COLORS                       */
                        /* ============================================= */

                        /* Info/Primary buttons - Dark mode */
                        .dark .fi-btn.fi-color-info.bg-custom-600,
                        .dark .fi-btn.fi-btn-color-info.bg-custom-600,
                        .dark .fi-btn.fi-color-primary.bg-custom-600,
                        .dark .fi-btn.fi-btn-color-primary.bg-custom-600 {
                            background-color: rgba(59 130 246 / 0.15) !important;
                            color: rgb(147 197 253) !important;
                            border: 1px solid rgba(59 130 246 / 0.3) !important;
                        }
                        .dark .fi-btn.fi-color-info.bg-custom-600:hover,
                        .dark .fi-btn.fi-btn-color-info.bg-custom-600:hover,
                        .dark .fi-btn.fi-color-primary.bg-custom-600:hover,
                        .dark .fi-btn.fi-btn-color-primary.bg-custom-600:hover {
                            background-color: rgba(59 130 246 / 0.25) !important;
                            border-color: rgba(59 130 246 / 0.5) !important;
                        }
                        .dark .fi-btn.fi-color-info.bg-custom-600 svg,
                        .dark .fi-btn.fi-btn-color-info.bg-custom-600 svg,
                        .dark .fi-btn.fi-color-primary.bg-custom-600 svg,
                        .dark .fi-btn.fi-btn-color-primary.bg-custom-600 svg {
                            color: rgb(147 197 253) !important;
                        }

                        /* Success buttons - Dark mode */
                        .dark .fi-btn.fi-color-success.bg-custom-600,
                        .dark .fi-btn.fi-btn-color-success.bg-custom-600 {
                            background-color: rgba(34 197 94 / 0.15) !important;
                            color: rgb(134 239 172) !important;
                            border: 1px solid rgba(34 197 94 / 0.3) !important;
                        }
                        .dark .fi-btn.fi-color-success.bg-custom-600:hover,
                        .dark .fi-btn.fi-btn-color-success.bg-custom-600:hover {
                            background-color: rgba(34 197 94 / 0.25) !important;
                            border-color: rgba(34 197 94 / 0.5) !important;
                        }
                        .dark .fi-btn.fi-color-success.bg-custom-600 svg,
                        .dark .fi-btn.fi-btn-color-success.bg-custom-600 svg {
                            color: rgb(134 239 172) !important;
                        }

                        /* Danger buttons - Dark mode */
                        .dark .fi-btn.fi-color-danger.bg-custom-600,
                        .dark .fi-btn.fi-btn-color-danger.bg-custom-600 {
                            background-color: rgba(239 68 68 / 0.15) !important;
                            color: rgb(252 165 165) !important;
                            border: 1px solid rgba(239 68 68 / 0.3) !important;
                        }
                        .dark .fi-btn.fi-color-danger.bg-custom-600:hover,
                        .dark .fi-btn.fi-btn-color-danger.bg-custom-600:hover {
                            background-color: rgba(239 68 68 / 0.25) !important;
                            border-color: rgba(239 68 68 / 0.5) !important;
                        }
                        .dark .fi-btn.fi-color-danger.bg-custom-600 svg,
                        .dark .fi-btn.fi-btn-color-danger.bg-custom-600 svg {
                            color: rgb(252 165 165) !important;
                        }

                        /* Warning buttons - Dark mode */
                        .dark .fi-btn.fi-color-warning.bg-custom-600,
                        .dark .fi-btn.fi-btn-color-warning.bg-custom-600 {
                            background-color: rgba(245 158 11 / 0.15) !important;
                            color: rgb(253 230 138) !important;
                            border: 1px solid rgba(245 158 11 / 0.3) !important;
                        }
                        .dark .fi-btn.fi-color-warning.bg-custom-600:hover,
                        .dark .fi-btn.fi-btn-color-warning.bg-custom-600:hover {
                            background-color: rgba(245 158 11 / 0.25) !important;
                            border-color: rgba(245 158 11 / 0.5) !important;
                        }
                        .dark .fi-btn.fi-color-warning.bg-custom-600 svg,
                        .dark .fi-btn.fi-btn-color-warning.bg-custom-600 svg {
                            color: rgb(253 230 138) !important;
                        }

                        /* Gray buttons - Dark mode */
                        .dark .fi-btn.fi-color-gray.bg-custom-600,
                        .dark .fi-btn.fi-btn-color-gray.bg-custom-600 {
                            background-color: rgba(161 161 170 / 0.15) !important;
                            color: rgb(212 212 216) !important;
                            border: 1px solid rgba(161 161 170 / 0.3) !important;
                        }
                        .dark .fi-btn.fi-color-gray.bg-custom-600:hover,
                        .dark .fi-btn.fi-btn-color-gray.bg-custom-600:hover {
                            background-color: rgba(161 161 170 / 0.25) !important;
                            border-color: rgba(161 161 170 / 0.5) !important;
                        }
                        .dark .fi-btn.fi-color-gray.bg-custom-600 svg,
                        .dark .fi-btn.fi-btn-color-gray.bg-custom-600 svg {
                            color: rgb(212 212 216) !important;
                        }


                        /* --- CALENDAR EVENTS (Modern UI with Tailwind Variables) --- */
                        
                        /* 1. Container & Global Overrides */
                        .ec {
                            border: none !important;
                            --ec-border-color: rgba(var(--gray-200), 0.4) !important;
                            --ec-text-color: rgb(var(--gray-700)) !important;
                            --ec-bg-color: transparent !important;
                        }
                        html.dark .ec {
                            --ec-border-color: rgba(var(--gray-700), 0.4) !important;
                            --ec-text-color: rgb(var(--gray-300)) !important;
                        }

                        /* 2. Modern Toolbar (Glassmorphism) */
                        .ec .ec-header .ec-toolbar {
                            background-color: rgba(255, 255, 255, 0.7) !important;
                            backdrop-filter: blur(12px) !important;
                            border: 1px solid rgba(var(--gray-200), 0.5) !important;
                            border-radius: 1.5rem !important; /* rounded-3xl */
                            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05) !important; /* shadow-lg soft */
                            padding: 0.75rem 1.25rem !important;
                            margin-bottom: 1.5rem !important;
                        }
                        html.dark .ec .ec-header .ec-toolbar {
                            background-color: rgba(24, 24, 27, 0.6) !important; /* zinc-950 alpha */
                            border-color: rgba(var(--gray-700), 0.5) !important;
                            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2) !important;
                        }

                        /* Toolbar Buttons (Pill Shape) */
                        .ec .ec-toolbar .ec-button {
                            border-radius: 9999px !important;
                            border: 1px solid transparent !important;
                            font-weight: 500 !important;
                            padding: 0.4rem 1rem !important;
                            transition: all 0.2s ease !important;
                        }
                        .ec .ec-toolbar .ec-button:hover {
                            background-color: rgba(var(--primary-500), 0.1) !important;
                            color: rgb(var(--primary-600)) !important;
                        }
                        .ec .ec-toolbar .ec-button.ec-active {
                            background-color: rgb(var(--primary-500)) !important;
                            color: white !important;
                            box-shadow: 0 4px 6px -1px rgba(var(--primary-500), 0.3) !important;
                        }

                        /* 3. Grid & Typography */
                        .ec-day-header {
                            text-transform: uppercase !important;
                            font-size: 0.75rem !important;
                            letter-spacing: 0.05em !important;
                            font-weight: 600 !important;
                            color: rgb(var(--gray-500)) !important;
                            padding-bottom: 10px !important;
                        }

                        /* Today Cell Indicator */
                        .ec .ec-day.ec-today {
                            background-color: transparent !important;
                        }
                        .ec .ec-day.ec-today .ec-day-header::before {
                            /* Dot indicator or highlight for today */
                            content: '';
                            display: inline-block;
                            width: 6px;
                            height: 6px;
                            background-color: rgb(var(--primary-500));
                            border-radius: 50%;
                            margin-right: 4px;
                            margin-bottom: 1px;
                        }
                        .ec .ec-day.ec-today .ec-day-header {
                            color: rgb(var(--primary-600)) !important;
                            font-weight: 700 !important;
                        }

                        /* 4. Events (Modern Pill & Interactive) */
                        body .ec .ec-event {
                            border-radius: 8px !important; /* Soft Squircle */
                            box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
                            border: 1px solid transparent !important;
                            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease !important;
                            margin-bottom: 2px !important;
                            font-size: 0.85rem !important;
                            padding: 2px 6px !important;
                        }
                        
                        body .ec .ec-event:hover {
                            transform: translateY(-2px) scale(1.01) !important;
                            box-shadow: 0 8px 12px -3px rgba(0,0,0,0.1) !important;
                            z-index: 50 !important;
                        }

                        /* --- Event Color Variants (Tailwind Variables) --- */

                        /* INFO / PROCESS (Blue) */
                        body .ec .ec-event.event-info {
                            background-color: rgba(var(--info-50), 0.8) !important;
                            color: rgb(var(--info-700)) !important;
                            border-color: rgba(var(--info-200), 0.5) !important;
                        }
                        html.dark body .ec .ec-event.event-info {
                            background-color: rgba(var(--info-900), 0.3) !important;
                            color: rgb(var(--info-300)) !important;
                            border-color: rgba(var(--info-700), 0.3) !important;
                        }

                        /* SUCCESS / DONE (Green) */
                        body .ec .ec-event.event-success {
                            background-color: rgba(var(--success-50), 0.8) !important;
                            color: rgb(var(--success-700)) !important;
                            border-color: rgba(var(--success-200), 0.5) !important;
                        }
                        html.dark body .ec .ec-event.event-success {
                            background-color: rgba(var(--success-900), 0.3) !important;
                            color: rgb(var(--success-300)) !important;
                            border-color: rgba(var(--success-700), 0.3) !important;
                        }

                        /* WARNING / PENDING (Amber) */
                        body .ec .ec-event.event-warning {
                            background-color: rgba(var(--warning-50), 0.8) !important;
                            color: rgb(var(--warning-700)) !important;
                            border-color: rgba(var(--warning-200), 0.5) !important;
                        }
                        html.dark body .ec .ec-event.event-warning {
                            background-color: rgba(var(--warning-900), 0.3) !important;
                            color: rgb(var(--warning-300)) !important;
                            border-color: rgba(var(--warning-700), 0.3) !important;
                        }

                        /* DANGER / CANCEL (Red) */
                        body .ec .ec-event.event-danger {
                            background-color: rgba(var(--danger-50), 0.8) !important;
                            color: rgb(var(--danger-700)) !important;
                            border-color: rgba(var(--danger-200), 0.5) !important;
                        }
                        html.dark body .ec .ec-event.event-danger {
                            background-color: rgba(var(--danger-900), 0.3) !important;
                            color: rgb(var(--danger-300)) !important;
                            border-color: rgba(var(--danger-700), 0.3) !important;
                        }

                        /* GRAY / DEFAULT */
                        body .ec .ec-event.event-gray {
                            background-color: rgba(var(--gray-50), 0.8) !important;
                            color: rgb(var(--gray-700)) !important;
                            border-color: rgba(var(--gray-200), 0.5) !important;
                        }
                         html.dark body .ec .ec-event.event-gray {
                            background-color: rgba(var(--gray-800), 0.5) !important;
                            color: rgb(var(--gray-300)) !important;
                            border-color: rgba(var(--gray-600), 0.3) !important;
                        }
                    </style>
                HTML
            )

            ->renderHook(
                'panels::body.end',
                fn() => view('filament.hooks.absensi-geolocation-script')

            )
            ->renderHook(
                'panels::global-search.after',
                fn() => view('filament.hooks.godmode-badge')
            )

            // --- 5. DRAGGABLE SIDEBAR LOGIC (NEW) ---
            ->renderHook(
                'panels::body.end',
                fn(): string => \Illuminate\Support\Facades\Blade::render(<<<'HTML'
                    <div id="sidebar-resizer" class="hidden md:block"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const resizer = document.getElementById('sidebar-resizer');
                            const sidebar = document.querySelector('.fi-sidebar');
                            
                            // Ambil ukuran tersimpan atau default 13rem
                            let sidebarWidth = localStorage.getItem('filament-sidebar-width') || '20rem';
                            
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
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => <<<'HTML'
                    <script>
                        document.addEventListener('click', function (event) {
                            // Ensure it's a real user click
                            if (!event.isTrusted) return;

                            const button = event.target.closest('.fi-sidebar-group-button');
                            if (!button) return;

                            // Use setTimeout to run this check AFTER Alpine has processed the click 
                            // and updated the 'aria-expanded' state.
                            setTimeout(() => {
                                // If the clicked group is now OPEN, we close others.
                                // If it's closed (user just closed it), we do nothing.
                                if (button.getAttribute('aria-expanded') === 'true') {
                                    document.querySelectorAll('.fi-sidebar-group-button').forEach(otherButton => {
                                        if (otherButton === button) return;
                                        
                                        if (otherButton.getAttribute('aria-expanded') === 'true') {
                                            otherButton.click();
                                        }
                                    });
                                }
                            }, 50);
                        });
                    </script>
                HTML
            );
    }
}
