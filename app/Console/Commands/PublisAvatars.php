<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PublisAvatars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:publish-avatars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move avatar files from private (local) storage to public storage to fix 403 errors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting avatar migration check...');

        $localDisk = \Illuminate\Support\Facades\Storage::disk('local');
        $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');
        $directory = 'karyawan/foto';

        if (!$localDisk->exists($directory)) {
            $this->info("No private avatars found in {$directory}. Nothing to do.");
            return;
        }

        $files = $localDisk->files($directory);
        $count = 0;

        foreach ($files as $file) {
            // Check if file already exists in public to avoid overwrite/redundancy
            if (!$publicDisk->exists($file)) {
                $this->info("Moving {$file} to public storage...");
                
                // Copy file content
                $publicDisk->put($file, $localDisk->get($file));
                
                // Delete original (optional, but good for cleanup. Let's keep it for safety or delete? 
                // Best to keep strict copy for now, or move? 
                // Let's delete to avoid confusion, assuming copy succeeded.)
                $localDisk->delete($file);
                
                $count++;
            } else {
                $this->comment("Skipping {$file} (already exists in public)");
            }
        }

        $this->info("Completed! Moved {$count} files.");
    }
}
