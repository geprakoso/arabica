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
        // Creating chat groups table to keep group level metadata.
        Schema::create('ch_groups', function (Blueprint $table) {
            $table->id(); // Primary key for the group row.
            $table->string('name'); // Human readable group name.
            $table->string('slug')->unique(); // Slug helps with quick lookups and prevents duplicates.
            $table->text('description')->nullable(); // Optional description for the group.
            $table->string('avatar')->nullable(); // Custom avatar filename if admins upload one.
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete(); // Track which user owns the group.
            $table->json('settings')->nullable(); // Store feature flags (allow_invites, etc.) for future growth.
            $table->timestamps(); // Maintain created_at/updated_at for auditing.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop table if the migration is rolled back.
        Schema::dropIfExists('ch_groups');
    }
};
