<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_penjualan', function (Blueprint $table) {
            $table->id('id_penjualan');
            $table->string('no_nota')->unique();
            $table->date('tanggal_penjualan');
            $table->text('catatan')->nullable();
            $table->foreignId('id_karyawan')
                ->nullable()
                ->constrained('md_karyawan')
                ->nullOnDelete();
            $table->foreignId('id_member')
                ->nullable()
                ->constrained('md_members')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_penjualan');
    }
};
