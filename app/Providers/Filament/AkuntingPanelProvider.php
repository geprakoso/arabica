<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Support\Carbon;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\StockInventory;
use Illuminate\Support\Facades\Storage;
use Orion\FilamentGreeter\GreeterPlugin;
use Shanerbaner82\PanelRoles\PanelRoles;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\ActiveMembersTable;
use App\Filament\Widgets\LowStockProductsTable;
use App\Filament\Widgets\PosSalesStatsOverview;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Widgets\TopSellingProductsTable;
use App\Filament\Widgets\OpenWeatherWidget;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Widgets\MonthlyRevenueTrendChart;
use App\Filament\Widgets\RecentPosTransactionsTable;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AkuntingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('akunting')
            ->path('akunting')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Blue,
                'secondary' => Color::Green,
                'accent' => Color::Red,
            ])
            // Register resources khusus Keuangan.
            ->resources([
                \App\Filament\Resources\Akunting\JenisAkunResource::class,
                \App\Filament\Resources\Akunting\KodeAkunResource::class,
                \App\Filament\Resources\Akunting\InputTransaksiTokoResource::class,
                \App\Filament\Resources\Akunting\LaporanInputTransaksiResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\PengaturanAkunting::class,
            ])
            ->widgets([
                OpenWeatherWidget::class,
                PosSalesStatsOverview::class,
                MonthlyRevenueTrendChart::class,
                ActiveMembersTable::class,
                LowStockProductsTable::class,
                RecentPosTransactionsTable::class,
                TopSellingProductsTable::class,
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
            ->plugin(FilamentShieldPlugin::make())

            ->plugin(
                PanelRoles::make()
                    ->roleToAssign('akunting')
                    ->restrictedRoles(['akunting', 'super_admin', 'petugas']),
            )

            ->plugins([
                GreeterPlugin::make()
                    ->timeSensitive(morningStart: 6, afternoonStart: 12, eveningStart: 17, nightStart: 22)
                    ->message(function (): string {
                        $hour = now()->timezone(config('app.timezone'))->hour;

                        $greeting = match (true) {
                            $hour < 6 => 'Selamat Pagi',
                            $hour < 12 => 'Selamat Siang',
                            $hour < 17 => 'Selamat Sore',
                            default => 'Selamat Malam',
                        };

                        return "{$greeting}, ";
                    })
                    ->title(text: 'Selamat datang di Point Of Sale Haen Komputer', enabled: true)
                    ->avatar(
                        size: 'w-16 h-16',
                        url: fn () => optional(Auth::user()?->karyawan)?->image_url
                            ? Storage::disk('public')->url(Auth::user()->karyawan->image_url)
                            : null,
                    )
                    ->sort(-10)
                    ->columnSpan('half'),
            ])

            //custom sidebar
            ->renderHook(
                'panels::head.end',
                fn(): string => <<<HTML
                    <style>
                    /* --- 0. SIDEBAR SETTINGS --- */
                    :root {
                        /* Default Filament: 16rem (256px). 
                           Kita kecilkan ~20% menjadi 13rem (208px) */
                        --sidebar-width: 13rem;
                    }

                    /* --- 1. SIDEBAR BACKGROUND & BORDER (Base) --- */
                    /* Light Mode */
                    .fi-sidebar {
                        background-color: #f0f0f0 !important;
                        border-right: 1px solid #e5e5e5 !important;
                        /* border-top: 1px solid #e5e5e5 !important; */
                        border-left: none !important;
                        border-bottom: none !important;
                    }
                    .fi-sidebar-header {
                        background-color: #f0f0f0 !important;
                        border-bottom: none !important;
                    }

                    /* Dark Mode */
                    html.dark .fi-sidebar {
                        background-color: #131313 !important;
                        border-right: 1px solid #262626 !important;
                        /* border-top: 1px solid #262626 !important; */
                        border-left: none !important;
                        border-bottom: none !important;
                    }
                    html.dark .fi-sidebar-header {
                        background-color: #131313 !important;
                        border-bottom: none !important;
                    }

                    /* --- 2. TYPOGRAPHY & COLORS (The Taste) --- */
                    
                    /* Reset Spacing */
                    .fi-sidebar-nav { gap: 0px !important; }
                    .fi-sidebar-group-items { gap: 1px !important; }
                    .fi-sidebar-sub-group-items { gap: 0px !important; }
                    .fi-sidebar-nav-groups.gap-y-7 { row-gap: 0px !important; }

                    /* A. ITEM MENU (Anak) */
                    .fi-sidebar-item-button {
                        padding-block: 5px !important;
                        padding-inline: 10px !important;
                        margin-inline: 8px !important;
                        border-radius: 6px !important;
                        
                        /* Font Settings */
                        font-size: 0.825rem !important; /* ~13.2px */
                        font-weight: 500 !important;
                        
                        /* Color - Light Mode Default */
                        color: #525252 !important; /* Neutral-600 (Abu tua elegan) */
                        
                        transition: all 0.15s ease-in-out !important;
                    }
                    
                    /* Color - Dark Mode Default */
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

                    /* C. HOVER STATE */
                    .fi-sidebar-item-button:hover {
                        background-color: rgba(0, 0, 0, 0.04) !important;
                        color: #171717 !important; /* Neutral-900 */
                    }
                    .fi-sidebar-item-button:hover .fi-sidebar-item-icon {
                        opacity: 1 !important;
                    }
                    
                    /* Dark Mode Hover */
                    html.dark .fi-sidebar-item-button:hover {
                        background-color: rgba(255, 255, 255, 0.05) !important;
                        color: #f5f5f5 !important;
                    }

                    /* D. ACTIVE STATE */
                    .fi-sidebar-item.fi-active .fi-sidebar-item-button {
                        background-color: rgba(0, 0, 0, 0.08) !important;
                        color: #000000 !important;
                        font-weight: 600 !important;
                    }
                    html.dark .fi-sidebar-item.fi-active .fi-sidebar-item-button {
                        background-color: rgba(255, 255, 255, 0.1) !important;
                        color: #ffffff !important;
                    }
                    /* Active Icon */
                    .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
                        opacity: 1 !important;
                    }

                    /* --- 3. GROUP HEADER (Induk) --- */
                    .fi-sidebar-group-button {
                        padding-block: 6px !important;
                        padding-inline: 10px !important;
                        margin-inline: 8px !important;
                        margin-top: 6px !important;
                        border-radius: 6px !important;
                        transition: background-color 0.1s ease !important;
                    }
                    .fi-sidebar-group-button:hover {
                        background-color: rgba(128, 128, 128, 0.05) !important;
                    }

                    /* Label Group */
                    .fi-sidebar-group-label {
                        padding: 0 !important;
                        font-size: 0.7rem !important;
                        text-transform: uppercase;
                        letter-spacing: 0.08em;
                        font-weight: 600;
                        color: #737373 !important; /* Neutral-500 */
                    }
                    html.dark .fi-sidebar-group-label {
                        color: #737373 !important;
                    }

                    /* Parent Active Logic (:has selector) */
                    .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-button {
                        background-color: rgba(128, 128, 128, 0.05) !important;
                    }
                    .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-label {
                        color: #171717 !important; /* Hitam saat child aktif */
                        opacity: 1 !important;
                    }
                    html.dark .fi-sidebar-group:has(.fi-sidebar-item.fi-active) .fi-sidebar-group-label {
                        color: #ffffff !important;
                    }

                    /* Utilities */
                    .fi-sidebar-group { border-top: none !important; }
                    .gap-y-1 { row-gap: 1px !important; }
                    .gap-y-2 { row-gap: 2px !important; }

                    /* --- 4. SCROLLBAR (Invisible Overlay) --- */
                    .fi-sidebar-nav {
                        scrollbar-width: thin;
                        scrollbar-color: transparent transparent;
                        transition: scrollbar-color 0.3s ease;
                    }
                    .fi-sidebar-nav:hover {
                        scrollbar-color: rgba(128, 128, 128, 0.2) transparent;
                    }
                    .fi-sidebar-nav::-webkit-scrollbar {
                        width: 4px !important;
                        height: 4px !important;
                    }
                    .fi-sidebar-nav::-webkit-scrollbar-track { background: transparent !important; }
                    .fi-sidebar-nav::-webkit-scrollbar-thumb {
                        background-color: transparent;
                        border-radius: 10px;
                    }
                    .fi-sidebar-nav:hover::-webkit-scrollbar-thumb {
                        background-color: rgba(128, 128, 128, 0.2) !important;
                    }
                    </style>
                HTML
            )

            ->renderHook(
                'panels::body.end',
                fn() => view('filament.hooks.absensi-geolocation-script')

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
