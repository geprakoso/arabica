<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

class PanelLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Auth::user();

        if ($user?->hasRole('super_admin')) {
            return redirect()->intended(route('filament.admin.pages.dashboard'));
        }

        if ($user?->hasRole('kasir')) {
            return redirect()->intended(route('filament.pos.pages.dashboard'));
        }

        if ($panelUrl = Filament::getCurrentPanel()?->getUrl()) {
            return redirect()->intended($panelUrl);
        }

        return redirect()->intended(route('home'));
    }
}
