<?php

namespace App\Providers;

use App\Support\ChatifyMessenger;
use App\Http\Responses\PanelLoginResponse;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Chatify\ChatifyMessenger as VendorChatifyMessenger;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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

                        return ['admin'];
                    })
                    ->labels([
                        'admin' => 'Admin',
                        'pos' => 'POS',
                    ])
                    ->icons([
                        'admin' => 'heroicon-o-cog-6-tooth',
                        'pos' => 'heroicon-o-shopping-cart',
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
