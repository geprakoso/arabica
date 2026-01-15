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
        Schema::create('tb_pembelian_jasa', function (Blueprint $table): void {
            $table->id('id_pembelian_jasa');
            $table->foreignId('id_pembelian')
                ->constrained('tb_pembelian', 'id_pembelian')
                ->cascadeOnDelete();
            $table->foreignId('jasa_id')
                ->nullable()
                ->constrained('md_jasa')
                ->nullOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('harga', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_pembelian_jasa');
    }
};
