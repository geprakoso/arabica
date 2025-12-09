<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ak_input_transaksi_tokos', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_transaksi');
            $table->foreignId('kode_jenis_akun_id')
                ->constrained('ak_jenis_akuns')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('kategori_transaksi');
            $table->decimal('nominal_transaksi', 15, 2);
            $table->text('keterangan_transaksi')->nullable();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('bukti_transaksi')->nullable();
            $table->foreignId('akun_transaksi_id')
                ->constrained('akun_transaksis')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ak_input_transaksi_tokos');
    }
};
