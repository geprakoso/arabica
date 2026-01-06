<?php

// app/Console/Commands/ServeWithLink.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class ServeWithLink extends Command
{
    protected $signature = 'custom:serve';
    protected $description = 'Run storage:link then serve';

    public function handle()
    {
        $this->info('Running storage:link...');
        Artisan::call('storage:link');

        // Mulai worker queue di background (connection: database).
        $this->info('Starting queue worker (database)...');
        $queueProcess = Process::fromShellCommandline('php artisan queue:work database', base_path());
        $queueProcess->setTimeout(null)->setIdleTimeout(null);
        $queueProcess->start(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $this->info('Starting server with large file upload support...');
        
        // Create a router script that handles static files properly
        $routerPath = base_path('server-router.php');
        
        // Use PHP built-in server with custom ini settings and proper router
        $cmd = sprintf(
            'php -d upload_max_filesize=128M -d post_max_size=130M -d memory_limit=512M -d max_execution_time=300 -d max_input_time=300 -S 0.0.0.0:8000 -t public %s',
            escapeshellarg($routerPath)
        );
        
        $serveProcess = Process::fromShellCommandline($cmd, base_path());
        $serveProcess->setTimeout(null)->setIdleTimeout(null);
        $serveProcess->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        // Jika server berhenti, hentikan worker queue agar tidak jadi orphan.
        if ($queueProcess->isRunning()) {
            $this->info('Stopping queue worker...');
            $queueProcess->stop(3);
        }
    }
}
