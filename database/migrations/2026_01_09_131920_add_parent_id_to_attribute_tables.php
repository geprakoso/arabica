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
        $tables = ['crosschecks', 'list_aplikasis', 'list_games', 'list_os'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Determine the singular version of the table name for the foreign key if needed, or just allow nullable integer.
                // Since these are self-referencing, we can constraint to the same table.
                $table->after('id', function (Blueprint $table) use ($tableName) {
                    $table->foreignId('parent_id')->nullable()->constrained($tableName)->nullOnDelete();
                });
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['crosschecks', 'list_aplikasis', 'list_games', 'list_os'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};
