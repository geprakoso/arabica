<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Chatify\ChatifyMessenger as VendorChatifyMessenger;
use App\Support\ChatifyMessenger;
use Illuminate\Support\Facades\URL;
use BezhanSalleh\PanelSwitch\PanelSwitch;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override Chatify messenger binding to gracefully handle push failures (e.g. offline Pusher).
        $this->app->bind(VendorChatifyMessenger::class, ChatifyMessenger::class);
        $this->app->bind('ChatifyMessenger', fn () => app(ChatifyMessenger::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (class_exists(PanelSwitch::class)) {
            PanelSwitch::configureUsing(function (PanelSwitch $switch) {
                $switch
                    ->panels(['admin', 'pos'])
                    ->labels([
                        'admin' => 'Admin',
                        'pos' => 'POS',
                    ])
                    ->icons([
                        'admin' => 'heroicon-o-cog-6-tooth',
                        'pos' => 'heroicon-o-shopping-cart',
                    ])
                    ->simple()
                    ->visible(fn () => auth()->check());
            });
        }

        if (app()->environment('production') || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }
    }
}
