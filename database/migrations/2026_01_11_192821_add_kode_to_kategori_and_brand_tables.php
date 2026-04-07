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
        Schema::table('md_kategori', function (Blueprint $table) {
            $table->string('kode', 5)->nullable()->after('nama_kategori');
        });
        Schema::table('md_brand', function (Blueprint $table) {
            $table->string('kode', 5)->nullable()->after('nama_brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_kategori', function (Blueprint $table) {
            $table->dropColumn('kode');
        });
        Schema::table('md_brand', function (Blueprint $table) {
            $table->dropColumn('kode');
        });
    }
};
