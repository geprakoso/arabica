<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_tukar_tambah', function (Blueprint $table): void {
            $table->id('id_tukar_tambah');
            $table->date('tanggal');
            $table->text('catatan')->nullable();
            $table->foreignId('id_karyawan')
                ->nullable()
                ->constrained('md_karyawan')
                ->nullOnDelete();
            $table->foreignId('penjualan_id')
                ->constrained('tb_penjualan', 'id_penjualan')
                ->cascadeOnDelete();
            $table->foreignId('pembelian_id')
                ->constrained('tb_pembelian', 'id_pembelian')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique('penjualan_id');
            $table->unique('pembelian_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_tukar_tambah');
    }
};
