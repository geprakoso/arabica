<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Chatify\ChatifyMessenger as VendorChatifyMessenger;
use App\Support\ChatifyMessenger;

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
        //
    }
}
