<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_pembelian', function (Blueprint $table) {
            if (! Schema::hasColumn('tb_pembelian', 'request_order_id')) {
                $table->foreignId('request_order_id')
                    ->nullable()
                    ->after('id_supplier')
                    ->constrained('request_orders')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_pembelian', function (Blueprint $table) {
            if (Schema::hasColumn('tb_pembelian', 'request_order_id')) {
                $table->dropForeign(['request_order_id']);
                $table->dropColumn('request_order_id');
            }
        });
    }
};
