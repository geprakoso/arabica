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
        Schema::create('jenis_akuns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kode_akun_id')
                ->constrained('kode_akuns')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('kode_jenis_akun')->unique();
            $table->string('nama_jenis_akun');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_akuns');
    }
};
