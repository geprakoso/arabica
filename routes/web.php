<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Filament\Pages\AppDashboard;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('filament.admin.auth.login');
    }

    $user = Auth::user();

    if ($user->hasRole('super_admin')) {
        return redirect()->route(AppDashboard::getRouteName('admin'));
    }

    if ($user->hasRole('kasir')) {
        return redirect()->route(AppDashboard::getRouteName('pos'));
    }

    if ($user->hasRole('akunting')) {
        return redirect()->route(AppDashboard::getRouteName('akunting'));
    }

    return redirect()->route(AppDashboard::getRouteName('admin'));
})->name('home');

// POS receipt preview/print
Route::get('/pos/receipt/{penjualan}', function (\App\Models\Penjualan $penjualan) {
    return view('pos.receipt', [
        'penjualan' => $penjualan->load(['items.produk', 'items.pembelianItem', 'karyawan']),
    ]);
})->name('pos.receipt');
