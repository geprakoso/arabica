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
        // Extend Chatify messages to differentiate user chats vs group chats.
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->string('conversation_type')
                ->default('user')
                ->after('to_id'); // Flag message routing target (user / group).
            $table->index(['to_id', 'conversation_type']); // Speed up fetch queries per conversation.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the conversation_type column if rolled back.
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->dropIndex('ch_messages_to_id_conversation_type_index'); // Drop composite index.
            $table->dropColumn('conversation_type'); // Remove the column entirely.
        });
    }
};
