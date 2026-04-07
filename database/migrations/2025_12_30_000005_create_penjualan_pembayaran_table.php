<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_penjualan_pembayaran', function (Blueprint $table) {
            $table->id('id_penjualan_pembayaran');
            $table->foreignId('id_penjualan')
                ->constrained('tb_penjualan', 'id_penjualan')
                ->cascadeOnDelete();
            $table->string('metode_bayar', 50);
            $table->foreignId('akun_transaksi_id')
                ->nullable()
                ->constrained('akun_transaksis')
                ->nullOnDelete();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_penjualan_pembayaran');
    }
};
