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
        Schema::create('request_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no_ro')->unique();
            $table->date('tanggal');
            $table->text('catatan')->nullable();
            $table->foreignId('karyawan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('request_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_order_id')->constrained('request_orders')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('md_produk')->cascadeOnDelete(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_order_items');
        Schema::dropIfExists('request_orders');
    }
};
