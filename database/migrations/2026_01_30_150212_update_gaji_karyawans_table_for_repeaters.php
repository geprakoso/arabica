<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gaji_karyawans', function (Blueprint $table) {
            $table->json('penerimaan')->nullable()->after('tanggal_pemberian');
            $table->json('potongan')->nullable()->after('penerimaan');
            $table->decimal('total_penerimaan', 15, 2)->default(0)->after('potongan');
            $table->decimal('total_potongan', 15, 2)->default(0)->after('total_penerimaan');
            $table->decimal('gaji_bersih', 15, 2)->default(0)->after('total_potongan');
            
            // Drop old columns if you want, but I'll keep them for safety or just drop if they conflict.
            // Actually user's previous schema had kategori_gaji, nominal, catatan.
            $table->dropColumn(['kategori_gaji', 'nominal', 'catatan']);
            $table->index('tanggal_pemberian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gaji_karyawans', function (Blueprint $table) {
            $table->string('kategori_gaji')->nullable();
            $table->decimal('nominal', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            
            $table->dropColumn(['penerimaan', 'potongan', 'total_penerimaan', 'total_potongan', 'gaji_bersih']);
        });
    }
};
