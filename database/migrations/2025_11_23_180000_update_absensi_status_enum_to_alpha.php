<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbarui enum status agar menggunakan ejaan "alpha".
     */
    public function up(): void
    {
        // Konversi data lama jika masih ada nilai 'alpa'
        DB::statement("UPDATE absensi SET status = 'alpha' WHERE status = 'alpa'");

        DB::statement(
            "ALTER TABLE absensi MODIFY status ENUM('hadir','izin','sakit','alpha') DEFAULT 'hadir'"
        );
    }

    /**
     * Kembalikan ke ejaan sebelumnya.
     */
    public function down(): void
    {
        DB::statement("UPDATE absensi SET status = 'alpa' WHERE status = 'alpha'");

        DB::statement(
            "ALTER TABLE absensi MODIFY status ENUM('hadir','izin','sakit','alpa') DEFAULT 'hadir'"
        );
    }
};
