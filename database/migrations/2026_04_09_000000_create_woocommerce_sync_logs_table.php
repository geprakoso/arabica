<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('woocommerce_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('woo_product_id')->nullable();
            $table->string('action', 50);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at');

            $table->index(['produk_id', 'action'], 'woocommerce_sync_logs_produk_action_idx');
            $table->index('synced_at', 'woocommerce_sync_logs_synced_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('woocommerce_sync_logs');
    }
};
