<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SimpleFilamentAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Gunakan guard 'web' secara eksplisit
        if (Auth::guard('web')->check()) {
            // User sudah login, pastikan Filament tahu user ini
            Auth::shouldUse('web');
            return $next($request);
        }

        // User belum login, redirect ke halaman login panel yang sedang diakses
        $panel = Filament::getCurrentPanel();
        
        if ($panel) {
            return redirect()->to($panel->getLoginUrl());
        }

        // Fallback ke admin login jika panel tidak terdeteksi
        return redirect()->route('filament.admin.auth.login');
    }
}
