<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('filament.admin.auth.login');
    }

    $user = Auth::user();

    if ($user->hasRole('super_admin')) {
        return redirect()->route('filament.admin.pages.dashboard');
    }

    if ($user->hasRole('kasir')) {
        return redirect()->route('filament.pos.pages.dashboard');
    }

    if ($user->hasRole('akunting')) {
        return redirect()->route('filament.akunting.pages.dashboard');
    }

    return redirect()->route('filament.admin.pages.dashboard');
})->name('home');

// POS receipt preview/print
Route::get('/pos/receipt/{penjualan}', function (\App\Models\Penjualan $penjualan) {
    return view('pos.receipt', [
        'penjualan' => $penjualan->load(['items.produk', 'items.pembelianItem', 'karyawan']),
    ]);
})->name('pos.receipt');
