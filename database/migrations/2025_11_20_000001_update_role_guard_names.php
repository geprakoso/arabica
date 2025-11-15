<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $defaultGuard = config('auth.defaults.guard', 'web');

        DB::table('roles')
            ->where(function ($query) use ($defaultGuard) {
                $query->whereNull('guard_name')
                    ->orWhere('guard_name', '!=', $defaultGuard);
            })
            ->update(['guard_name' => $defaultGuard]);
    }

    public function down(): void
    {
        // no-op
    }
};
