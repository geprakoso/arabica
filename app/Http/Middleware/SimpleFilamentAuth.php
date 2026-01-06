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
            $user = Auth::guard('web')->user();
            $panel = Filament::getCurrentPanel();

            // Tolak akses jika user belum punya role agar tidak masuk panel.
            if ($user && ! $user->roles()->exists()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($panel) {
                    return redirect()
                        ->to($panel->getLoginUrl())
                        ->with('error', 'Akun belum memiliki role. Hubungi admin untuk akses.');
                }

                return redirect()
                    ->route('filament.admin.auth.login')
                    ->with('error', 'Akun belum memiliki role. Hubungi admin untuk akses.');
            }

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
