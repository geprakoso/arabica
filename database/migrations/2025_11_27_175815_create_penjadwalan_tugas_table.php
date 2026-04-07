<?php

use App\Models\User;
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
        Schema::create('penjadwalan_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'karyawan_id')->constrained('users');
            $table->foreignIdFor(User::class, 'created_by')->constrained('users');
            $table->string('judul');
            $table->text('deskripsi');
            $table->date('tanggal_mulai')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status');
            $table->string('prioritas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjadwalan_tugas');
    }
};
