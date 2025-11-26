<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_penjualan_item', function (Blueprint $table) {
            $table->id('id_penjualan_item');
            $table->foreignId('id_penjualan')
                ->constrained('tb_penjualan', 'id_penjualan')
                ->cascadeOnDelete();
            $table->foreignId('id_produk')
                ->constrained('md_produk')
                ->restrictOnDelete();
            $table->foreignId('id_pembelian_item');
            $table->unsignedInteger('qty');
            $table->decimal('hpp', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->enum('kondisi', ['baru', 'bekas'])->default('baru');
            $table->timestamps();
        });

        Schema::table('tb_penjualan_item', function (Blueprint $table) {
            $batchTable = 'tb_pembelian_item';
            $referenceColumn = 'id_pembelian_item';

            if (! Schema::hasTable($batchTable)) {
                return;
            }

            if (! Schema::hasColumn($batchTable, $referenceColumn) && Schema::hasColumn($batchTable, 'id')) {
                $referenceColumn = 'id';
            }

            if (! Schema::hasColumn($batchTable, $referenceColumn)) {
                return;
            }

            $table->foreign('id_pembelian_item')
                ->references($referenceColumn)
                ->on($batchTable)
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_penjualan_item');
    }
};
