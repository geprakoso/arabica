<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalender_events', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('tipe');
            $table->text('deskripsi')->nullable();
            $table->string('lokasi')->nullable();
            $table->dateTime('mulai');
            $table->dateTime('selesai');
            $table->boolean('all_day')->default(false);
            $table->foreignIdFor(User::class, 'created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalender_events');
    }
};
