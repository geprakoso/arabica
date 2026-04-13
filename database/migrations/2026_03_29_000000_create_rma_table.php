<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_rma', function (Blueprint $table) {
            $table->bigIncrements('id_rma');
            $table->date('tanggal');
            $table->unsignedBigInteger('id_pembelian_item');
            $table->string('status_garansi', 32)->default('di_packing');
            $table->string('rma_di_mana', 255);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['id_pembelian_item', 'status_garansi'], 'tb_rma_batch_status_idx');
            $table->foreign('id_pembelian_item')
                ->references('id_pembelian_item')
                ->on('tb_pembelian_item')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_rma');
    }
};
