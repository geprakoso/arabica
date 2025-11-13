<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('md_produk', function (Blueprint $table) {
            $table->id();
            $table->string('nama_produk');
            $table->foreignId('kategori_id')->constrained('md_kategori')->cascadeOnDelete(false);
            $table->foreignId('brand_id')->constrained('md_brand')->cascadeOnDelete(false);
            $table->string('sku')->unique();
            $table->decimal('berat', 10, 2)->nullable();
            $table->decimal('panjang', 8, 2)->nullable();
            $table->decimal('lebar', 8, 2)->nullable();
            $table->decimal('tinggi', 8, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->longText('deskripsi')->nullable();
            $table->timestamps();
            $table->foreignId('diubah_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            //
        });

        DB::statement('ALTER TABLE md_produk ADD CONSTRAINT chk_md_produk_non_negative
            CHECK (
                (berat   IS NULL OR berat   >= 0) AND
                (panjang IS NULL OR panjang >= 0) AND
                (lebar   IS NULL OR lebar   >= 0) AND
                (tinggi  IS NULL OR tinggi  >= 0)
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('md_produk');
    }
};
