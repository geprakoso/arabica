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
        Schema::create('stock_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_batch_id')->constrained('stock_batches')->cascadeOnDelete();
            $table->enum('type', ['purchase', 'sale', 'opname', 'adjustment', 'rma_return', 'initial_sync']);
            $table->integer('qty_change'); // bisa negatif (keluar) atau positif (masuk)
            $table->integer('qty_before');
            $table->integer('qty_after');
            $table->string('reference_type'); // 'Pembelian', 'Penjualan', 'StockOpname', 'StockAdjustment', 'Rma'
            $table->unsignedBigInteger('reference_id');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Index untuk query yang sering dilakukan
            $table->index(['stock_batch_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};
