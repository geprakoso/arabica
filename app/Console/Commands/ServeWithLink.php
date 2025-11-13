<?php

// app/Console/Commands/ServeWithLink.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ServeWithLink extends Command
{
    protected $signature = 'custom:serve';
    protected $description = 'Run storage:link then serve';

    public function handle()
    {
        $this->info('Running storage:link...');
        Artisan::call('storage:link');
        $this->info('Starting server...');
        passthru('php artisan serve');
    }
}

