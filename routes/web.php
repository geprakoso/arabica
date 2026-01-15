<?php

use App\Filament\Pages\AppDashboard;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/test-auth', function () {
    $user = Auth::user();

    return response()->json([
        'is_logged_in' => Auth::check(),
        'user_id' => $user->id ?? null,
        'user_name' => $user->name ?? null,
        'roles' => $user ? $user->getRoleNames() : [],
        // 'panel_id' => Filament::getPanel('admin')->getId(),
        'can_access_panel' => $user ? $user->canAccessPanel(Filament::getPanel('admin')) : false,
    ]);
});

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

Route::get('/penjualan/invoice/{penjualan}', function (\App\Models\Penjualan $penjualan) {
    return view('penjualan.invoice', [
        'penjualan' => $penjualan->load([
            'items.produk',
            'items.pembelianItem.pembelian',
            'jasaItems.jasa',
            'member',
            'karyawan',
            'akunTransaksi',
            'pembayaran.akunTransaksi',
        ]),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjualan.invoice');

Route::get('/penjualan/invoice-simple/{penjualan}', function (\App\Models\Penjualan $penjualan) {
    return view('penjualan.invoice-simple', [
        'penjualan' => $penjualan->load([
            'items.produk',
            'jasaItems.jasa',
            'member',
            'karyawan',
            'pembayaran.akunTransaksi',
        ]),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjualan.invoice.simple');

Route::get('/penjadwalan-service/print/{record}', function (\App\Models\PenjadwalanService $record) {
    return view('filament.resources.penjadwalan-service.print', [
        'record' => $record->load(['member', 'technician', 'jasa']),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjadwalan-service.print');

Route::get('/tukar-tambah/invoice/{tukarTambah}', function (\App\Models\TukarTambah $tukarTambah) {
    return view('tukar-tambah.invoice', [
        'tukarTambah' => $tukarTambah->load([
            'karyawan',
            'penjualan.items.produk',
            'penjualan.jasaItems.jasa',
            'penjualan.member',
            'penjualan.karyawan',
            'penjualan.pembayaran.akunTransaksi',
            'pembelian.items.produk',
            'pembelian.supplier',
            'pembelian.karyawan',
        ]),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('tukar-tambah.invoice');
Route::get('/tukar-tambah/invoice-simple/{tukarTambah}', function (\App\Models\TukarTambah $tukarTambah) {
    return view('tukar-tambah.invoice-simple', [
        'tukarTambah' => $tukarTambah->load([
            'karyawan',
            'penjualan.items.produk',
            'penjualan.jasaItems.jasa',
            'penjualan.member',
            'penjualan.karyawan',
            'penjualan.pembayaran.akunTransaksi',
            'pembelian.items.produk',
            'pembelian.supplier',
            'pembelian.karyawan',
        ]),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('tukar-tambah.invoice.simple');
Route::get('/penjadwalan-service/print-crosscheck/{record}', function (\App\Models\PenjadwalanService $record) {
    return view('filament.resources.penjadwalan-service.print-crosscheck', [
        'record' => $record->load(['member', 'technician', 'jasa', 'crosschecks', 'listAplikasis', 'listGames', 'listOs']),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjadwalan-service.print-crosscheck');
