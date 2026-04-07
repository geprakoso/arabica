<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_laba_rugis', function (Blueprint $table): void {
            $table->string('month_key')->primary();
            $table->date('month_start')->nullable();
            $table->decimal('total_hpp', 15, 2)->default(0);
            $table->decimal('total_beban', 15, 2)->default(0);
            $table->decimal('laba_rugi', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_laba_rugis');
    }
};
