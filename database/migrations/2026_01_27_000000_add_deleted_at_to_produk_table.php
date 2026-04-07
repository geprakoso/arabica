<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('md_produk', function (Blueprint $table): void {
            if (! Schema::hasColumn('md_produk', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('md_produk', function (Blueprint $table): void {
            if (Schema::hasColumn('md_produk', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
