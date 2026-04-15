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
        // Index untuk filter tanggal (paling sering digunakan)
        Schema::table('at_libur_cuti', function (Blueprint $table) {
            $table->index('mulai_tanggal');
            $table->index('user_id');
            $table->index('status_pengajuan');
        });

        // Index untuk laporan_pengajuan_cutis
        Schema::table('laporan_pengajuan_cutis', function (Blueprint $table) {
            $table->index(['libur_cuti_id', 'status_pengajuan']); // Composite index
            $table->index('created_at'); // Untuk sorting
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('at_libur_cuti', function (Blueprint $table) {
            $table->dropIndex(['mulai_tanggal']);
            // user_id tidak di-drop karena dipakai foreign key constraint
            $table->dropIndex(['status_pengajuan']);
        });

        Schema::table('laporan_pengajuan_cutis', function (Blueprint $table) {
            $table->dropIndex(['libur_cuti_id', 'status_pengajuan']);
            $table->dropIndex(['created_at']);
        });
    }
};
