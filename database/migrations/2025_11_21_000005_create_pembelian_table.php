<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_pembelian', function (Blueprint $table) {
            $table->id('id_pembelian');
            $table->string('no_po')->unique();
            $table->date('tanggal');
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->enum('tipe_pembelian', ['ppn', 'non_ppn'])->default('non_ppn');
            $table->enum('jenis_pembayaran', ['lunas', 'tempo'])->default('lunas');
            $table->date('tgl_tempo')->nullable();
            $table->foreignId('id_karyawan')
                ->nullable()
                ->constrained('md_karyawan')
                ->nullOnDelete();
            $table->foreignId('id_supplier')
                ->nullable()
                ->constrained('md_suppliers')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_pembelian');
    }
};
