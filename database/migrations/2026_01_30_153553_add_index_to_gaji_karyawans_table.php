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
        Schema::table('gaji_karyawans', function (Blueprint $table) {
            if (! collect(DB::select("SHOW INDEXES FROM gaji_karyawans"))->pluck('Key_name')->contains('gaji_karyawans_tanggal_pemberian_index')) {
                $table->index('tanggal_pemberian');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gaji_karyawans', function (Blueprint $table) {
            $table->dropIndex(['tanggal_pemberian']);
        });
    }
};
