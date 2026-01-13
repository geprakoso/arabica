<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penjadwalan_tugas_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjadwalan_tugas_id')->constrained('penjadwalan_tugas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // Migrate existing data
        $tasks = DB::table('penjadwalan_tugas')->whereNotNull('karyawan_id')->get();
        foreach ($tasks as $task) {
            DB::table('penjadwalan_tugas_user')->insert([
                'penjadwalan_tugas_id' => $task->id,
                'user_id' => $task->karyawan_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop old column
        Schema::table('penjadwalan_tugas', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
            $table->dropColumn('karyawan_id');
        });
    }

    public function down(): void
    {
        Schema::table('penjadwalan_tugas', function (Blueprint $table) {
            $table->foreignId('karyawan_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Restore data (take first user)
        $pivots = DB::table('penjadwalan_tugas_user')->get();
        foreach ($pivots as $pivot) {
            DB::table('penjadwalan_tugas')
                ->where('id', $pivot->penjadwalan_tugas_id)
                ->update(['karyawan_id' => $pivot->user_id]);
        }

        Schema::dropIfExists('penjadwalan_tugas_user');
    }
};
