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
        Schema::create('laporan_pengajuan_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libur_cuti_id')
                ->constrained('at_libur_cuti')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('status_pengajuan')->default('pending');
            $table->timestamps();

            $table->unique('libur_cuti_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_pengajuan_cutis');
    }
};
