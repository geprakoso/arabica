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
            // Hapus kolom status_dokumen jika ada
            if (Schema::hasColumn('tb_penjualan', 'status_dokumen')) {
                $table->dropColumn('status_dokumen');
            }

            // Hapus kolom foto_dokumen jika ada
            if (Schema::hasColumn('tb_penjualan', 'foto_dokumen')) {
                $table->dropColumn('foto_dokumen');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table) {
            // Tambahkan kembali kolom status_dokumen
            if (! Schema::hasColumn('tb_penjualan', 'status_dokumen')) {
                $table->string('status_dokumen', 20)
                    ->default('final')
                    ->after('status_pembayaran');
            }

            // Tambahkan kembali kolom foto_dokumen
            if (! Schema::hasColumn('tb_penjualan', 'foto_dokumen')) {
                $table->json('foto_dokumen')->nullable()->after('status_dokumen');
            }
        });
    }
};
