<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R01, R14, R17: Create stock_batches table
     * - R01: Sistem batch tracking
     * - R14: Qty tetap (tidak berubah) untuk view konsistensi
     * - R17: Pessimistic locking untuk mencegah race condition/oversell
     */
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke item pembelian
            $table->foreignId('pembelian_item_id')
                ->constrained('tb_pembelian_item', 'id_pembelian_item')
                ->onDelete('cascade');
            
            // Relasi ke produk (untuk query cepat)
            $table->foreignId('produk_id')
                ->constrained('md_produk')
                ->onDelete('restrict');
            
            // R14: Qty total dari pembelian (tetap, tidak berubah)
            $table->unsignedInteger('qty_total');
            
            // R17: Qty yang masih tersedia (berkurang saat penjualan)
            $table->unsignedInteger('qty_available');
            
            // R17: Tracking waktu lock untuk debugging
            $table->timestamp('locked_at')->nullable();
            
            $table->timestamps();
            
            // R17: Index untuk locking performance
            $table->index('pembelian_item_id');
            $table->index(['produk_id', 'qty_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
