<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tb_penjualan')) {
            return;
        }

        Schema::table('tb_penjualan', function (Blueprint $table): void {
            $table->decimal('total', 15, 2)->default(0)->after('id_member');
            $table->decimal('diskon_total', 15, 2)->default(0)->after('total');
            $table->decimal('grand_total', 15, 2)->default(0)->after('diskon_total');
            $table->string('metode_bayar', 50)->nullable()->after('grand_total');
            $table->decimal('tunai_diterima', 15, 2)->nullable()->after('metode_bayar');
            $table->decimal('kembalian', 15, 2)->nullable()->after('tunai_diterima');
            $table->foreignId('gudang_id')
                ->nullable()
                ->after('kembalian')
                ->constrained('md_gudang')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tb_penjualan')) {
            return;
        }

        Schema::table('tb_penjualan', function (Blueprint $table): void {
            $table->dropForeign(['gudang_id']);
            $table->dropColumn([
                'total',
                'diskon_total',
                'grand_total',
                'metode_bayar',
                'tunai_diterima',
                'kembalian',
                'gudang_id',
            ]);
        });
    }
};
