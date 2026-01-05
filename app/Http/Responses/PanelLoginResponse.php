<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportRedirects\Redirector;
use App\Filament\Pages\AppDashboard;

class PanelLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Auth::user();

        if ($user?->hasRole('super_admin')) {
            if ($url = $this->routeIfExists(AppDashboard::getRouteName('admin'))) {
                return redirect()->intended($url);
            }

            if ($panelUrl = Filament::getPanel('admin')?->getUrl()) {
                return redirect()->intended($panelUrl);
            }
        }

        if ($user?->hasRole('kasir')) {
            if ($url = $this->routeIfExists(AppDashboard::getRouteName('pos'))) {
                return redirect()->intended($url);
            }

            if ($panelUrl = Filament::getPanel('pos')?->getUrl()) {
                return redirect()->intended($panelUrl);
            }
        }

        if ($user?->hasRole('akunting')) {
            if ($url = $this->routeIfExists(AppDashboard::getRouteName('akunting'))) {
                return redirect()->intended($url);
            }

            if ($panelUrl = Filament::getPanel('akunting')?->getUrl()) {
                return redirect()->intended($panelUrl);
            }
        }

        if ($panelUrl = Filament::getCurrentPanel()?->getUrl()) {
            return redirect()->intended($panelUrl);
        }

        return redirect()->intended(route('home'));
    }

    private function routeIfExists(string $routeName): ?string
    {
        return Route::has($routeName) ? route($routeName) : null;
    }
}
