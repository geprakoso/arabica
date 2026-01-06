<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Livewire\WithFileUploads;
use Illuminate\Contracts\View\View;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class DatabaseBackup extends Page
{
    use HasPageShield;
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static string $view = 'filament.pages.database-backup';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $title = 'Backup & Restore Database';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Restore Database')
                    ->description('Upload file .sql, .zip, atau .gzip untuk mengembalikan database. PERINGATAN: Tindakan ini akan menimpa seluruh data yang ada!')
                    ->schema([
                        FileUpload::make('backupFile')
                            ->label('File Backup (.sql, .zip, .gzip)')
                            ->maxSize(1024 * 100) // 100 MB
                            ->required()
                            ->disk('local') 
                            ->directory('temp-backups')
                            ->visibility('private'),
                    ])
            ]);
    }

    public function export()
    {
        try {
            $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
            $path = storage_path('app/' . $filename);
            
            // Ensure directory exists
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $database = config('database.connections.mysql.database');
            $port = config('database.connections.mysql.port', 3306);

            // Construct command parts
            $cmdParts = [
                'mysqldump',
                "--user=\"{$username}\"",
                "--host=\"{$host}\"",
                "--port=\"{$port}\"",
                "--no-tablespaces",
                "--ssl-mode=DISABLED", // Bypass SSL for Docker MySQL
            ];

            if (!empty($password)) {
                $cmdParts[] = "--password=\"{$password}\"";
            }

            // Fix database name quoting: remove unwanted curly braces and spaces
            $cmdParts[] = "\"{$database}\""; 
            $cmdParts[] = "> \"{$path}\"";

            $command = implode(' ', $cmdParts);
            
            $result = Process::run($command);

            if ($result->successful()) {
                if (file_exists($path) && filesize($path) > 0) {
                     return response()->download($path)->deleteFileAfterSend(true);
                } else {
                     Notification::make()
                        ->title('Export Gagal')
                        ->body('File backup kosong atau tidak berhasil dibuat.')
                        ->danger()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('Export Gagal')
                    ->body($result->errorOutput())
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi Kesalahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function import()
    {
        try {
            // Get validated data from form state
            $data = $this->form->getState();
            $uploadedFilePath = $data['backupFile'];
            
            // Convert storage path to absolute system path
            $path = Storage::disk('local')->path($uploadedFilePath);

            if (!file_exists($path)) {
                 throw new \Exception('File backup tidak ditemukan di storage: ' . $path);
            }

            // Manual Validation for File Extension (since we removed strict rules on frontend)
            $allowedExtensions = ['sql', 'txt', 'zip', 'gz', 'gzip'];
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                 // Try to check mime content type if extension is missing or weird, 
                 // but for now let's just enforce extension as it's what users expect.
                 // Clean up the invalid file
                 Storage::disk('local')->delete($uploadedFilePath);
                 throw new \Exception('Tipe file tidak valid. Harap upload file .sql, .zip, atau .gzip.');
            }
            
            // Handle Compression
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $sqlPath = $path;
            
            if (in_array(strtolower($extension), ['zip'])) {
                 $sqlPath = dirname($path) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.extracted.sql';
                 // Unzip and pipe to file (simplest way assuming single sql file or using the first one)
                 // unzip -p prints to stdout
                 $unzipCmd = "unzip -p \"{$path}\" > \"{$sqlPath}\"";
                 Process::run($unzipCmd);
            } elseif (in_array(strtolower($extension), ['gz', 'gzip'])) {
                 $sqlPath = dirname($path) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.extracted.sql';
                 // gunzip -c writes to stdout
                 $gunzipCmd = "gunzip -c \"{$path}\" > \"{$sqlPath}\"";
                 Process::run($gunzipCmd);
            }

            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $database = config('database.connections.mysql.database');
            $port = config('database.connections.mysql.port', 3306);

            $cmdParts = [
                'mysql',
                "--user=\"{$username}\"",
                "--host=\"{$host}\"",
                "--port=\"{$port}\"",
                "--ssl-mode=DISABLED", // Bypass SSL for Docker MySQL
            ];

            if (!empty($password)) {
                $cmdParts[] = "--password=\"{$password}\"";
            }

            $cmdParts[] = "\"{$database}\"";
            $cmdParts[] = "< \"{$sqlPath}\"";

            $command = implode(' ', $cmdParts);
            
            Notification::make()
                ->title('Proses Import')
                ->body('Sedang memproses database...')
                ->info()
                ->send();

            $result = Process::run($command);
            
            // Cleanup extracted file if it was compressed
            if ($sqlPath !== $path && file_exists($sqlPath)) {
                @unlink($sqlPath);
            }

            if ($result->successful()) {
                Notification::make()
                    ->title('Import Berhasil')
                    ->body('Database telah berhasil dipulihkan.')
                    ->success()
                    ->send();
                
                // Clear form
                $this->form->fill();
            } else {
                Notification::make()
                    ->title('Import Gagal')
                    ->body($result->errorOutput())
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi Kesalahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
