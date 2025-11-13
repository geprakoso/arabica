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
        Schema::create('md_jasa', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama_jasa')->unique();
            $table->string('sku')->unique();
            $table->decimal('harga', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('estimasi_waktu_jam')->nullable();
            $table->longText('deskripsi')->nullable();
            $table->timestamps();
            $table->foreignId('diubah_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('image_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('md_jasa');
    }
};
