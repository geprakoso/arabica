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
        Schema::table('ak_kode_akuns', function (Blueprint $table) {
            $table->string('kelompok_neraca')->nullable()->after('kategori_akun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ak_kode_akuns', function (Blueprint $table) {
            $table->dropColumn('kelompok_neraca');
        });
    }
};
