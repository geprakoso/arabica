<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// POS receipt preview/print
Route::get('/pos/receipt/{penjualan}', function (\App\Models\Penjualan $penjualan) {
    return view('pos.receipt', [
        'penjualan' => $penjualan->load(['items.produk', 'items.pembelianItem', 'karyawan']),
    ]);
})->name('pos.receipt');
