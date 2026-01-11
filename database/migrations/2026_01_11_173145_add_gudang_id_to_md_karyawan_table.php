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
        Schema::table('md_karyawan', function (Blueprint $table) {
            $table->foreignId('gudang_id')
                ->nullable()
                ->after('role_id')
                ->constrained('md_gudang')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_karyawan', function (Blueprint $table) {
            $table->dropForeign(['gudang_id']);
            $table->dropColumn('gudang_id');
        });
    }
};
