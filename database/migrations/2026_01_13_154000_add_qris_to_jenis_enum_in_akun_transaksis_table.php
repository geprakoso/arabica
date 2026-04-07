<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'qris' to the jenis enum column
        DB::statement("ALTER TABLE akun_transaksis MODIFY COLUMN jenis ENUM('tunai', 'transfer', 'e-wallet', 'gyro', 'qris') DEFAULT 'transfer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'qris' from the jenis enum column (revert to original)
        DB::statement("ALTER TABLE akun_transaksis MODIFY COLUMN jenis ENUM('tunai', 'transfer', 'e-wallet', 'gyro') DEFAULT 'transfer'");
    }
};
