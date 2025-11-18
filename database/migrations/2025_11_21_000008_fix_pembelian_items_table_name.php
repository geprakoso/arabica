<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tb_pembelianitem') && ! Schema::hasTable('tb_pembelian_item')) {
            Schema::rename('tb_pembelianitem', 'tb_pembelian_item');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tb_pembelian_item') && ! Schema::hasTable('tb_pembelianitem')) {
            Schema::rename('tb_pembelian_item', 'tb_pembelianitem');
        }
    }
};
