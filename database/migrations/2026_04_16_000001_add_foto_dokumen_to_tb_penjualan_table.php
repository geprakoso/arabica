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
        Schema::table('tb_penjualan', function (Blueprint $table) {
            // Tambahkan kolom foto_dokumen jika belum ada
            if (! Schema::hasColumn('tb_penjualan', 'foto_dokumen')) {
                $table->json('foto_dokumen')->nullable()->after('catatan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('tb_penjualan', 'foto_dokumen')) {
                $table->dropColumn('foto_dokumen');
            }
        });
    }
};
