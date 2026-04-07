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
        Schema::table('md_gudang', function (Blueprint $table) {
            $table->string('provinsi')->nullable()->after('lokasi_gudang');
            $table->string('kota')->nullable()->after('provinsi');
            $table->string('kecamatan')->nullable()->after('kota');
            $table->string('kelurahan')->nullable()->after('kecamatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_gudang', function (Blueprint $table) {
            $table->dropColumn(['provinsi', 'kota', 'kecamatan', 'kelurahan']);
        });
    }
};
