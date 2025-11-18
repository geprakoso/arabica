<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_orders', function (Blueprint $table) {
            if (Schema::hasColumn('request_orders', 'karyawan_id')) {
                $table->dropForeign(['karyawan_id']);
                $table->dropColumn('karyawan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('request_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('request_orders', 'karyawan_id')) {
                $table->foreignId('karyawan_id')
                    ->nullable()
                    ->after('catatan')
                    ->constrained('md_karyawan')
                    ->nullOnDelete();
            }
        });
    }
};
