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
        Schema::create('md_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama_kategori');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('diubah_oleh_id')->nullable()->constrained('users')->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('md_kategori');
    }
};
