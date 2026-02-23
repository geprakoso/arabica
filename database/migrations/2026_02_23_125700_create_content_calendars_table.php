<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->dateTime('tanggal_publish')->nullable();
            $table->string('content_pillar')->nullable();
            $table->json('platform')->nullable();
            $table->string('tipe_konten')->nullable();
            $table->string('status')->default('draft');
            $table->text('caption')->nullable();
            $table->string('hashtag')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('pic')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('visual')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_calendars');
    }
};
