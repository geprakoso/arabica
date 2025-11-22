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
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel Users (Karyawan)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk');
            $table->time('jam_keluar')->nullable(); // Nullable karena saat absen masuk, jam keluar belum ada
            // Durasi kerja (dihitung saat pulang)
            $table->integer('durasi_menit')->default(0);
            // Status kehadiran
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha'])->default('hadir');
            $table->text('keterangan')->nullable(); // Untuk catatan jika telat atau izin
            // kordinat (disimpan untuk history)
            $table->decimal('lat_absen', 10, 8)->nullable();
            $table->decimal('long_absen', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
