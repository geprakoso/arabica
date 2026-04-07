<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_tukar_tambah', function (Blueprint $table): void {
            if (! Schema::hasColumn('tb_tukar_tambah', 'id_member')) {
                $table->foreignId('id_member')
                    ->nullable()
                    ->after('id_karyawan')
                    ->constrained('md_members')
                    ->nullOnDelete();
            }
        });

        // Populate existing data
        $tukarTambahs = DB::table('tb_tukar_tambah')
            ->join('tb_penjualan', 'tb_tukar_tambah.penjualan_id', '=', 'tb_penjualan.id_penjualan')
            ->select('tb_tukar_tambah.id_tukar_tambah', 'tb_penjualan.id_member')
            ->get();

        foreach ($tukarTambahs as $tt) {
            DB::table('tb_tukar_tambah')
                ->where('id_tukar_tambah', $tt->id_tukar_tambah)
                ->update(['id_member' => $tt->id_member]);
        }
    }

    public function down(): void
    {
        Schema::table('tb_tukar_tambah', function (Blueprint $table): void {
            if (Schema::hasColumn('tb_tukar_tambah', 'id_member')) {
                $table->dropForeign(['id_member']);
                $table->dropColumn('id_member');
            }
        });
    }
};
