<?php

namespace App\Providers;

use App\Support\ChatifyMessenger;
use App\Http\Responses\PanelLoginResponse;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Chatify\ChatifyMessenger as VendorChatifyMessenger;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register filament-edit-profile Livewire components globally
        // The plugin registers these in Filament's panel boot() which doesn't run for /livewire/update requests
        $this->app->booted(function () {
            if (class_exists(\Joaopaulolndev\FilamentEditProfile\Livewire\EditProfileForm::class)) {
                Livewire::component('edit_profile_form', \Joaopaulolndev\FilamentEditProfile\Livewire\EditProfileForm::class);
                Livewire::component('edit_password_form', \Joaopaulolndev\FilamentEditProfile\Livewire\EditPasswordForm::class);
                Livewire::component('browser_sessions_form', \Joaopaulolndev\FilamentEditProfile\Livewire\BrowserSessionsForm::class);
            }
        });
        // Override Chatify messenger binding to gracefully handle push failures (e.g. offline Pusher).
        $this->app->bind(VendorChatifyMessenger::class, ChatifyMessenger::class);
        $this->app->bind('ChatifyMessenger', fn() => app(ChatifyMessenger::class));

        // Redirect Filament logins to panel-specific dashboards.
        $this->app->bind(LoginResponseContract::class, PanelLoginResponse::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentShield::configurePermissionIdentifierUsing(function (string $resource): string {
            $identifier = Str::of($resource)
                ->afterLast('Resources\\')
                ->before('Resource')
                ->replace('\\', '')
                ->snake()
                ->replace('_', '::')
                ->toString();

            $segments = explode('::', $identifier);
            $normalized = [];

            foreach ($segments as $segment) {
                if ($segment === '' || end($normalized) === $segment) {
                    continue;
                }

                $normalized[] = $segment;
            }

            return implode('::', $normalized);
        });

        if (class_exists(PanelSwitch::class)) {
            PanelSwitch::configureUsing(function (PanelSwitch $switch) {
                $switch
                    ->panels(function () {
                        $user = Auth::user();

                        if (! $user) {
                            return [];
                        }

                        if ($user->hasRole('super_admin')) {
                            return [];
                        }

                        if ($user->hasRole('kasir')) {
                            return ['pos'];
                        }

                        if ($user->hasRole('akunting')) {
                            return ['akunting'];
                        }

                        return ['admin'];
                    })
                    ->labels([
                        'admin' => 'Admin',
                        'pos' => 'POS',
                        'akunting' => 'Keuangan'
                    ])
                    ->icons([
                        'admin' => 'heroicon-o-cog-6-tooth',
                        'pos' => 'heroicon-o-shopping-cart',
                        'akunting' => 'heroicon-o-bank'
                    ])
                    ->simple()
                    ->visible(fn() => Auth::check());
            });
        }

        if (app()->environment('production') || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }

        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultPaginationPageOption(25)
                ->striped();
        });
    }
}
