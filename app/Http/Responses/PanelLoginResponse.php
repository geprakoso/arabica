<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;
use App\Filament\Pages\AppDashboard;

class PanelLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Auth::user();

        if ($user?->hasRole('super_admin')) {
            return redirect()->intended(route(AppDashboard::getRouteName('admin')));
        }

        if ($user?->hasRole('kasir')) {
            return redirect()->intended(route(AppDashboard::getRouteName('pos')));
        }

        if ($user?->hasRole('akunting')) {
            return redirect()->intended(route(AppDashboard::getRouteName('akunting')));
        }

        if ($panelUrl = Filament::getCurrentPanel()?->getUrl()) {
            return redirect()->intended($panelUrl);
        }

        return redirect()->intended(route('home'));
    }
}
