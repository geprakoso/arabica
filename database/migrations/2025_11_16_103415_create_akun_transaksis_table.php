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
        Schema::create('akun_transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_akun')->unique();
            $table->string('kode_akun')->unique();
            $table->enum('jenis', ['tunai', 'transfer', 'e-wallet', 'gyro'])->default('transfer');
            $table->string('nama_bank')->nullable();
            $table->string('nama_rekening')->nullable();
            $table->string('no_rekening')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('diubah_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun_transaksis');
    }
};
