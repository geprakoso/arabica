<?php

use App\Models\User;
use App\Models\Jasa;
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
        Schema::create('penjadwalan_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('md_members');
            $table->string('nama_perangkat');
            $table->string('kelengkapan')->nullable();
            $table->text('keluhan');
            $table->text('catatan_teknisi')->nullable();
            $table->string('no_resi')->unique();
            $table->string('status')->default('pending');
            $table->foreignIdFor(User::class, 'technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('jasa_id')->nullable()->constrained('md_jasa')->nullOnDelete();
            $table->date('estimasi_selesai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjadwalan_service');
    }
};
