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
        Schema::create('profile_perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan');
            $table->text('alamat_perusahaan')->nullable();
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();
            $table->string('npwp')->nullable();
            $table->string('tanggal_pkp')->nullable();
            $table->string('nama_pkp')->nullable();
            $table->string('alamat_pkp')->nullable();
            $table->string('telepon_pkp')->nullable();
            $table->string('logo_link')->nullable();
            $table->string('lat_perusahaan');
            $table->string('long_perusahaan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_perusahaan');
    }
};
