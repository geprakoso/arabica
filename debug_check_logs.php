<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PenjadwalanTugas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

// Find or Create a test task 1 Day
$task = PenjadwalanTugas::firstOrCreate([
    'judul' => 'Debug Task 1 Day',
], [
    'tanggal_mulai' => now(),
    'deadline' => now(), // 1 Day
    'status' => \App\Enums\StatusTugas::Pending,
    'prioritas' => 'sedang',
    'created_by' => 1,
    'deskripsi' => 'Debug',
]);

// Update dates to be sure
$task->update([
    'tanggal_mulai' => now(),
    'deadline' => now(),
]);

echo "Task ID: " . $task->id . "\n";
echo "Start: " . $task->tanggal_mulai->toDateTimeString() . "\n";
echo "End: " . $task->deadline->toDateTimeString() . "\n";

// Emulate Logic
$start = $task->tanggal_mulai;
$end = $task->deadline;
$diff = $start->diffInDays($end) + 1;
$isToday = $start->isToday();

echo "Diff: $diff\n";
echo "IsToday: " . ($isToday ? 'YES' : 'NO') . "\n";

if (in_array($diff, [1, 2, 3]) && $isToday) {
    echo "Logic Result: PRESET $diff\n";
} else {
    echo "Logic Result: CUSTOM\n";
}
