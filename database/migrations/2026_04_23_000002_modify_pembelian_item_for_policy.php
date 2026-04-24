<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R02, R03, R04: Modify tb_pembelian_item
     * - R04: Drop serials (SN & Garansi digantikan subtotal)
     * - R03: Add subtotal column
     * - R02: Add unique constraint untuk produk+kondisi per pembelian
     */
    public function up(): void
    {
        Schema::table('tb_pembelian_item', function (Blueprint $table) {
            // R04: Hapus kolom serials (SN tidak digunakan lagi)
            if (Schema::hasColumn('tb_pembelian_item', 'serials')) {
                $table->dropColumn('serials');
            }
            
            // R03: Add subtotal column (Qty × HPP)
            $table->decimal('subtotal', 15, 2)->default(0)->after('kondisi');
            
            // R02: Unique constraint - produk sama dengan kondisi berbeda boleh
            // tapi produk+kondisi sama tidak boleh duplikat dalam 1 pembelian
            $table->unique(
                ['id_pembelian', 'id_produk', 'kondisi'],
                'unique_produk_kondisi_per_pembelian'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_pembelian_item', function (Blueprint $table) {
            // Restore serials column
            $table->json('serials')->nullable()->after('kondisi');
            
            // Drop subtotal
            $table->dropColumn('subtotal');
            
            // Drop unique constraint
            $table->dropUnique('unique_produk_kondisi_per_pembelian');
        });
    }
};
