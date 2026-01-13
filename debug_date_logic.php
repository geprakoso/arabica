<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;

$start = Carbon::parse('2026-01-13'); // Today according to prompt
$end = Carbon::parse('2026-01-13');

$now = Carbon::now();

echo "App Timezone: " . config('app.timezone') . "\n";
echo "Now: " . $now->toDateTimeString() . "\n";
echo "Start: " . $start->toDateTimeString() . "\n";
echo "End: " . $end->toDateTimeString() . "\n";

$diff = $start->diffInDays($end) + 1;
echo "Diff: " . $diff . "\n";

$isToday = $start->isToday();
echo "IsToday: " . ($isToday ? 'YES' : 'NO') . "\n";

if (in_array($diff, [1, 2, 3]) && $isToday) {
    echo "Logic: MATCHES preset " . $diff . "\n";
} else {
    echo "Logic: FALLBACK to custom\n";
}
