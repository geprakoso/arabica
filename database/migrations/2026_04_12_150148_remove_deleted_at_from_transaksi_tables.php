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
        // Hapus kolom deleted_at dari tb_penjualan
        Schema::table('tb_penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('tb_penjualan', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });

        // Hapus kolom deleted_at dari tb_pembelian
        Schema::table('tb_pembelian', function (Blueprint $table) {
            if (Schema::hasColumn('tb_pembelian', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });

        // Hapus kolom deleted_at dari tb_tukar_tambah
        Schema::table('tb_tukar_tambah', function (Blueprint $table) {
            if (Schema::hasColumn('tb_tukar_tambah', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tambahkan kembali kolom deleted_at ke tb_penjualan
        Schema::table('tb_penjualan', function (Blueprint $table) {
            if (! Schema::hasColumn('tb_penjualan', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Tambahkan kembali kolom deleted_at ke tb_pembelian
        Schema::table('tb_pembelian', function (Blueprint $table) {
            if (! Schema::hasColumn('tb_pembelian', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Tambahkan kembali kolom deleted_at ke tb_tukar_tambah
        Schema::table('tb_tukar_tambah', function (Blueprint $table) {
            if (! Schema::hasColumn('tb_tukar_tambah', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }
};
