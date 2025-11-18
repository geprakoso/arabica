<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_pembelianitem', function (Blueprint $table) {
            $table->id('id_pembelianitem');
            $table->foreignId('id_pembelian')
                ->constrained('tb_pembelian', 'id_pembelian')
                ->cascadeOnDelete();
            $table->foreignId('id_produk')
                ->constrained('md_produk')
                ->restrictOnDelete();
            $table->decimal('hpp', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->unsignedInteger('qty');
            $table->enum('kondisi', ['baru', 'bekas'])->default('baru');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_pembelianitem');
    }
};
