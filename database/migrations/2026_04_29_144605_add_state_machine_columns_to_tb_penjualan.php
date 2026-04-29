<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table): void {
            // Pastikan status_dokumen ada dulu
            if (! Schema::hasColumn('tb_penjualan', 'status_dokumen')) {
                $table->string('status_dokumen', 20)->default('final')->after('status_pembayaran');
            }

            if (! Schema::hasColumn('tb_penjualan', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            if (! Schema::hasColumn('tb_penjualan', 'void_used')) {
                $table->boolean('void_used')->default(false);
            }
            if (! Schema::hasColumn('tb_penjualan', 'posted_at')) {
                $table->timestamp('posted_at')->nullable();
            }
            if (! Schema::hasColumn('tb_penjualan', 'posted_by_id')) {
                $table->foreignId('posted_by_id')->nullable()->constrained('users');
            }
            if (! Schema::hasColumn('tb_penjualan', 'voided_at')) {
                $table->timestamp('voided_at')->nullable();
            }
            if (! Schema::hasColumn('tb_penjualan', 'voided_by_id')) {
                $table->foreignId('voided_by_id')->nullable()->constrained('users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table): void {
            // Drop foreign keys first
            try {
                $table->dropForeign(['posted_by_id']);
            } catch (\Exception $e) {}
            try {
                $table->dropForeign(['voided_by_id']);
            } catch (\Exception $e) {}

            $columns = ['is_locked', 'void_used', 'posted_at', 'posted_by_id', 'voided_at', 'voided_by_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('tb_penjualan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
