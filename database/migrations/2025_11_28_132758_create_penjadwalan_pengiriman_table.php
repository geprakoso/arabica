<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penjadwalan_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('no_resi');
            $table->foreignId('penjualan_id')->constrained(table: 'tb_penjualan', column: 'id_penjualan');
            $table->foreignId('member_id')->constrained('md_members');
            $table->foreignIdFor(User::class, 'karyawan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alamat');
            $table->string('penerima_nama')->nullable();
            $table->string('penerima_no_hp')->nullable();
            $table->date('tanggal_penerimaan')->nullable();
            $table->string('status');
            $table->text('catatan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjadwalan_pengiriman');
    }
};
