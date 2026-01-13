<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_pembelian_pembayaran', function (Blueprint $table): void {
            $table->id('id_pembelian_pembayaran');
            $table->foreignId('id_pembelian')
                ->constrained('tb_pembelian', 'id_pembelian')
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
        Schema::dropIfExists('tb_pembelian_pembayaran');
    }
};
