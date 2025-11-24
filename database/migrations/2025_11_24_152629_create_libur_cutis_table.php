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
        Schema::create('at_libur_cuti', function (Blueprint $table) {
            $table->id();
            // Pastikan ini mengarah ke tabel yang benar. 
            // Jika untuk karyawan, gunakan member_id.
            // Jika table kamu 'md_members', relasinya seperti ini:
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('keperluan'); // Nanti diisi value dari Enum: 'libur', 'cuti'
            // Gunakan string agar fleksibel
            $table->date('mulai_tanggal');
            $table->date('sampai_tanggal')->nullable(); 
            $table->string('status_pengajuan'); // Nanti diisi value: 'pending', 'diterima', 'reject'
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('at_libur_cuti');
    }
};
