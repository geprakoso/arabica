<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify enum column
        DB::statement("ALTER TABLE stock_mutations MODIFY COLUMN type ENUM('purchase','sale','opname','adjustment','rma_return','initial_sync','sale_return') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_mutations MODIFY COLUMN type ENUM('purchase','sale','opname','adjustment','rma_return','initial_sync') NOT NULL");
    }
};
