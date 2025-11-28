<?php

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\PembelianItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockAdjustmentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $gudang = Gudang::first();
        $batch = PembelianItem::first();

        if (! $batch) {
            return;
        }

        $adjustment = StockAdjustment::firstOrCreate(
            ['kode' => 'SA-SEED-0001'],
            [
                'tanggal' => now()->subDays(3),
                'status' => 'draft',
                'gudang_id' => $gudang?->id,
                'user_id' => $user?->id,
                'catatan' => 'Penyesuaian stok awal (seed).',
            ]
        );

        StockAdjustmentItem::updateOrCreate(
            [
                'stock_adjustment_id' => $adjustment->id,
                'pembelian_item_id' => $batch->id_pembelian_item,
            ],
            [
                'produk_id' => $batch->produk_id ?? $batch->id_produk,
                'qty' => 1,
                'keterangan' => 'Penambahan stok sebagai contoh data.',
            ]
        );
    }
}
