<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $badIcons = ['hugeicons-user-add', 'hugeicons-user-add-01'];

        DB::table('notifications')
            ->select('id', 'data')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($badIcons) {
                foreach ($rows as $row) {
                    $data = json_decode($row->data ?? '', true);

                    if (! is_array($data)) {
                        continue;
                    }

                    if (! isset($data['icon']) || ! in_array($data['icon'], $badIcons, true)) {
                        continue;
                    }

                    $data['icon'] = 'heroicon-m-user-plus';

                    DB::table('notifications')
                        ->where('id', $row->id)
                        ->update(['data' => json_encode($data)]);
                }
            });
    }

    public function down(): void
    {
        // No rollback needed; original icon names were invalid.
    }
};
