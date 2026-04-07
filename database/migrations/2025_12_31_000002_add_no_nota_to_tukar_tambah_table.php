<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tb_tukar_tambah')) {
            return;
        }

        Schema::table('tb_tukar_tambah', function (Blueprint $table): void {
            if (! Schema::hasColumn('tb_tukar_tambah', 'no_nota')) {
                $table->string('no_nota')->nullable()->unique()->after('id_tukar_tambah');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tb_tukar_tambah')) {
            return;
        }

        Schema::table('tb_tukar_tambah', function (Blueprint $table): void {
            if (Schema::hasColumn('tb_tukar_tambah', 'no_nota')) {
                $table->dropUnique(['no_nota']);
                $table->dropColumn('no_nota');
            }
        });
    }
};
