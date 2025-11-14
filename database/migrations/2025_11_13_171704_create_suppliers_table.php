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
        Schema::create('md_suppliers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nama_supplier')->unique();
            $table->string('email')->nullable();
            $table->string('no_hp')->unique();
            $table->string('alamat')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kota')->nullable();
            $table->string('kecamatan')->nullable();
        });

        Schema::create('md_supplier_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('md_suppliers')->cascadeOnDelete();
            $table->string('nama_agen');
            $table->string('no_hp_agen');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('md_supplier_agents');
        Schema::dropIfExists('md_suppliers');
    }
};
