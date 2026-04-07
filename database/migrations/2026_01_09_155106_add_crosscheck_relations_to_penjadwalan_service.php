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
        Schema::table('penjadwalan_service', function (Blueprint $table) {
            $table->boolean('has_crosscheck')->default(false);
        });

        // Pivot for Crosscheck
        Schema::create('penjadwalan_service_crosscheck', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjadwalan_service_id')->constrained('penjadwalan_service')->cascadeOnDelete();
            $table->foreignId('crosscheck_id')->constrained()->cascadeOnDelete();
        });

        // Pivot for ListAplikasi
        Schema::create('penjadwalan_service_list_aplikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjadwalan_service_id')->constrained('penjadwalan_service')->cascadeOnDelete();
            $table->foreignId('list_aplikasi_id')->constrained()->cascadeOnDelete();
        });

        // Pivot for ListGame
        Schema::create('penjadwalan_service_list_game', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjadwalan_service_id')->constrained('penjadwalan_service')->cascadeOnDelete();
            $table->foreignId('list_game_id')->constrained()->cascadeOnDelete();
        });

        // Pivot for ListOs
        Schema::create('penjadwalan_service_list_os', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjadwalan_service_id')->constrained('penjadwalan_service')->cascadeOnDelete();
            $table->foreignId('list_os_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjadwalan_service_list_os');
        Schema::dropIfExists('penjadwalan_service_list_game');
        Schema::dropIfExists('penjadwalan_service_list_aplikasi');
        Schema::dropIfExists('penjadwalan_service_crosscheck');

        Schema::table('penjadwalan_service', function (Blueprint $table) {
            $table->dropColumn('has_crosscheck');
        });
    }
};
