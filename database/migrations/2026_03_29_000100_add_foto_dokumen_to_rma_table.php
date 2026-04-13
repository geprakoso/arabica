<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_rma', function (Blueprint $table) {
            $table->json('foto_dokumen')->nullable()->after('rma_di_mana');
        });
    }

    public function down(): void
    {
        Schema::table('tb_rma', function (Blueprint $table) {
            $table->dropColumn('foto_dokumen');
        });
    }
};
