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
        Schema::create('validation_logs', function (Blueprint $table) {
            $table->id();

            // UUID untuk grouping batch validation
            $table->uuid('uuid');

            // Konteks
            $table->string('source_type', 50); // 'Penjualan', 'TukarTambah', 'Pembelian'
            $table->string('source_action', 50); // 'create', 'update', 'delete'

            // User Info
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 255)->nullable();

            // Error Detail
            $table->string('validation_type', 50); // 'duplicate', 'stock', 'required', 'format'
            $table->string('field_name', 100)->nullable(); // 'items_temp', 'qty', 'id_produk'
            $table->text('error_message');
            $table->string('error_code', 50)->nullable(); // VALIDATION_DUPLICATE, etc

            // Input Data (yang menyebabkan error)
            $table->json('input_data')->nullable();

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();

            // Severity
            $table->string('severity', 20)->default('warning'); // 'info', 'warning', 'error', 'critical'

            // Resolution
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamp('created_at')->nullable();

            // Indexes
            $table->index(['source_type', 'source_action'], 'idx_source');
            $table->index('user_id', 'idx_user');
            $table->index('validation_type', 'idx_validation_type');
            $table->index('severity', 'idx_severity');
            $table->index('created_at', 'idx_created_at');
            $table->index('uuid', 'idx_uuid');
            $table->index('is_resolved', 'idx_is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_logs');
    }
};
