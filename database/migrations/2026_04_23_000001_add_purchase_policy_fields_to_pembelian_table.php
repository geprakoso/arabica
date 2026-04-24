<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R11, R16: Add grand_total, total_paid, is_locked to tb_pembelian
     */
    public function up(): void
    {
        Schema::table('tb_pembelian', function (Blueprint $table) {
            // R11: Simpan grand total permanen untuk konsistensi data
            $table->decimal('grand_total', 15, 2)->default(0)->after('harga_jual');
            
            // R06-R08: Total pembayaran terkumpul untuk kalkulasi status
            $table->decimal('total_paid', 15, 2)->default(0)->after('grand_total');
            
            // R16: Status lock final (irreversible)
            $table->boolean('is_locked')->default(false)->after('total_paid');
            
            // R13: NO TT (Tanda Terima) reference
            $table->string('no_tt')->nullable()->after('is_locked');
            $table->index('no_tt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_pembelian', function (Blueprint $table) {
            $table->dropColumn(['grand_total', 'total_paid', 'is_locked', 'no_tt']);
        });
    }
};
