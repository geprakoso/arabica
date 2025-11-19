<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelian_request_order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembelian_id');
            $table->unsignedBigInteger('request_order_id');
            $table->timestamps();

            $table->foreign('pembelian_id')
                ->references('id_pembelian')
                ->on('tb_pembelian')
                ->cascadeOnDelete();

            $table->foreign('request_order_id')
                ->references('id')
                ->on('request_orders')
                ->cascadeOnDelete();

            $table->unique(['pembelian_id', 'request_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_request_order');
    }
};
