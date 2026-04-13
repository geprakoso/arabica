<?php

namespace App\Console\Commands;

use App\Support\CacheHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearArabicaCache extends Command
{
    protected $signature = 'arabica:clear-cache 
                            {--tag= : Hapus cache dengan tag tertentu (penjualan, pembelian, tukar_tambah, master)}
                            {--all : Hapus semua cache Arabica}';

    protected $description = 'Clear Arabica application cache';

    public function handle(): int
    {
        $this->info('🧹 Menghapus cache Arabica...');

        if ($this->option('all')) {
            CacheHelper::clearAll();
            $this->info('✅ Semua cache Arabica telah dihapus!');

            return self::SUCCESS;
        }

        $tag = $this->option('tag');

        if ($tag) {
            $validTags = [
                'penjualan' => CacheHelper::TAG_PENJUALAN,
                'pembelian' => CacheHelper::TAG_PEMBELIAN,
                'tukar_tambah' => CacheHelper::TAG_TUKAR_TAMBAH,
                'master' => CacheHelper::TAG_MASTER,
            ];

            if (! isset($validTags[$tag])) {
                $this->error("❌ Tag '{$tag}' tidak valid!");
                $this->info('Tag yang tersedia: '.implode(', ', array_keys($validTags)));

                return self::FAILURE;
            }

            CacheHelper::flush([$validTags[$tag]]);
            $this->info("✅ Cache dengan tag '{$tag}' telah dihapus!");

            return self::SUCCESS;
        }

        // Default: tampilkan info cache
        $this->info('📊 Cache Info:');
        $info = CacheHelper::info();

        foreach ($info as $key => $value) {
            $this->line("  • {$key}: {$value}");
        }

        $this->newLine();
        $this->info('💡 Penggunaan:');
        $this->line('  php artisan arabica:clear-cache --all');
        $this->line('  php artisan arabica:clear-cache --tag=penjualan');
        $this->line('  php artisan arabica:clear-cache --tag=pembelian');

        return self::SUCCESS;
    }
}
