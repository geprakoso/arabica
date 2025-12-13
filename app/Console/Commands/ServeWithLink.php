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

        $this->info('Starting server...');
        $serveProcess = Process::fromShellCommandline('php artisan serve --host=0.0.0.0', base_path());
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
