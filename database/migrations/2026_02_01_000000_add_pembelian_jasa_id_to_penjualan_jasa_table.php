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
        Schema::table('tb_penjualan_jasa', function (Blueprint $table): void {
            $table->foreignId('pembelian_jasa_id')
                ->nullable()
                ->after('pembelian_item_id')
                ->constrained('tb_pembelian_jasa', 'id_pembelian_jasa')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_penjualan_jasa', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pembelian_jasa_id');
        });
    }
};
