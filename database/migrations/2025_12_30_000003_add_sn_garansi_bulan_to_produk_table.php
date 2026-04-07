<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('md_produk')) {
            return;
        }

        Schema::table('md_produk', function (Blueprint $table): void {
            $table->string('sn')->nullable()->unique()->after('sku');
            $table->string('garansi')->nullable()->after('sn');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('md_produk')) {
            return;
        }

        Schema::table('md_produk', function (Blueprint $table): void {
            $table->dropUnique(['sn']);
            $table->dropColumn(['sn', 'garansi']);
        });
    }
};
