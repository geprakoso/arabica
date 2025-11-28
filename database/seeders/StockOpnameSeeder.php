<?php

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\PembelianItem;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockOpnameSeeder extends Seeder
{
    public function run(): void
    {
        $gudang = Gudang::first();
        $user = User::where('email', 'admin@example.com')->first();
        $batch = PembelianItem::first();

        if (! $batch) {
            return;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $stokSistem = (int) ($batch->{$qtyColumn} ?? 0);

        $opname = StockOpname::firstOrCreate(
            ['kode' => 'SO-SEED-0001'],
            [
                'tanggal' => now()->subDay(),
                'status' => 'draft',
                'gudang_id' => $gudang?->id,
                'user_id' => $user?->id,
                'catatan' => 'Stock opname awal (seed).',
            ]
        );

        StockOpnameItem::updateOrCreate(
            [
                'stock_opname_id' => $opname->id,
                'pembelian_item_id' => $batch->id_pembelian_item,
            ],
            [
                'produk_id' => $batch->produk_id ?? $batch->id_produk,
                'stok_sistem' => $stokSistem,
                'stok_fisik' => max(0, $stokSistem - 1),
                'catatan' => 'Penyesuaian minor untuk contoh data.',
            ]
        );
    }
}
