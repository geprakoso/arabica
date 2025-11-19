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
        // Pivot table to link chat groups with their members.
        Schema::create('ch_group_user', function (Blueprint $table) {
            $table->id(); // Primary key for the pivot row.
            $table->foreignId('group_id')->constrained('ch_groups')->cascadeOnDelete(); // Group reference.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Member reference.
            $table->string('role')->default('member'); // Store member role (owner/admin/member) for permissions.
            $table->timestamps(); // Track membership changes for audits.
            $table->unique(['group_id', 'user_id']); // Prevent duplicate membership rows.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop pivot table on rollback.
        Schema::dropIfExists('ch_group_user');
    }
};
