<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_pembelian', function (Blueprint $table): void {
            if (! Schema::hasColumn('tb_pembelian', 'nota_supplier')) {
                $table->string('nota_supplier')->nullable()->after('no_po');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_pembelian', function (Blueprint $table): void {
            if (Schema::hasColumn('tb_pembelian', 'nota_supplier')) {
                $table->dropColumn('nota_supplier');
            }
        });
    }
};
