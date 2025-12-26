<?php

namespace App\Filament\Widgets;

// [DOCS] Import class dasar widget dari plugin EightyNine (Advanced Widget)
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

// [DOCS] Import Model Eloquent kita (Database Tables)
use App\Models\Penjualan;
use App\Models\Pembelian;

// [DOCS] Import Carbon untuk manipulasi tanggal/waktu (Sangat penting di Dashboard!)
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

class AdvancedStatsOverviewWidget extends BaseWidget
{
    use HasWidgetShield;
    // Mengatur urutan tampilan widget di dashboard (urutan ke-3)
    protected static ?int $sort = 1;

    // [FUNGSI UTAMA] Ini fungsi yang dipanggil Filament untuk merender statistik
    protected function getStats(): array
    {
        // // [DEBUG] Cek waktu saat ini: dd($now);
        $now = Carbon::now();

        // -----------------------------------------------------------------------
        // [LOGIKA WAKTU] Menentukan Rentang Waktu (Start & End Date)
        // -----------------------------------------------------------------------
        
        // Periode Bulan Ini (Misal: 1 Des - 31 Des)
        // copy() digunakan agar variabel $now asli tidak berubah saat dimanipulasi
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth   = $now->copy()->endOfMonth();

        // Periode Bulan Lalu (Misal: 1 Nov - 30 Nov)
        // subMonthNoOverflow() aman untuk tanggal 31 (misal 31 Maret -> 28 Feb)
        $startOfLastMonth = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth   = $now->copy()->subMonthNoOverflow()->endOfMonth();

        // -----------------------------------------------------------------------
        // [RELASI-A] MENGHITUNG TOTAL PENDAPATAN
        // Memanggil fungsi helper di bawah (lihat tanda [RELASI-A] di bawah)
        // -----------------------------------------------------------------------
        $thisMonthTotal = $this->sumTotalPenjualanForPeriod($startOfMonth, $endOfMonth);
        $lastMonthTotal = $this->sumTotalPenjualanForPeriod($startOfLastMonth, $endOfLastMonth);

        // // [DEBUG] Cek hasil hitungan: dump($thisMonthTotal, $lastMonthTotal);

        // [LOGIKA MATEMATIKA] Menghitung Persentase Kenaikan/Penurunan (Delta)
        // Rumus: ((Baru - Lama) / Lama) * 100
        $delta = $lastMonthTotal > 0
            ? round((($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100)
            : null; // Jika bulan lalu 0, set null agar tidak error "division by zero"

        return [
            // ===================================================================
            // WIDGET 1: PENDAPATAN BULAN INI
            // ===================================================================
            Stat::make('Pendapatan Bulan Ini', $this->formatCurrency($thisMonthTotal))
                ->icon('heroicon-o-banknotes')
                ->iconColor('success') // Warna ikon hijau
                // Logika ternary untuk menampilkan deskripsi naik/turun
                ->description($delta !== null ? ($delta >= 0 ? "+{$delta}% dibanding bulan lalu" : "{$delta}% dibanding bulan lalu") : 'Data bulan lalu tidak tersedia')
                ->descriptionIcon($delta === null || $delta >= 0 ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'before')
                ->descriptionColor($delta === null || $delta >= 0 ? 'success' : 'danger')
                
                // [RELASI-C] CHART SPARKLINE (Grafik Garis Kecil)
                // Memanggil fungsi getDailyPendapatanChart untuk data grafik
                ->chart($this->getDailyPendapatanChart($startOfMonth, $endOfMonth))
                ->chartColor('success'),

            // ===================================================================
            // WIDGET 2: PRODUK TERJUAL
            // ===================================================================
            Stat::make(
                'Produk Terjual',
                // [RELASI-B] Menghitung Qty
                $this->formatNumber(
                    $this->sumTotalQtyForPeriod($startOfMonth, $endOfMonth)
                )
            )
                ->icon('heroicon-o-shopping-cart')
                ->description('Qty terjual bulan ini')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->descriptionColor('primary')
                ->iconColor('warning') // Warna kuning/oranye
                // [RELASI-D] Chart Qty
                ->chart($this->getDailyBarangChart($startOfMonth, $endOfMonth))
                ->chartColor('warning'),

            // ===================================================================
            // WIDGET 3: TOTAL PEMBELIAN (PENGELUARAN)
            // ===================================================================
            Stat::make(
                'Total Pembelian',
                // [RELASI-E] Menghitung Pembelian
                $this->formatCurrency(
                    $this->sumTotalPembelianForPeriod($startOfMonth, $endOfMonth)
                )
            )
                ->icon('heroicon-o-arrow-down-tray')
                ->description('Pembelian bulan ini')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->descriptionColor('primary')
                ->iconColor('primary')
                // Note: Kamu memanggil getDailyBarangChart disini, apakah sengaja? 
                // Seharusnya mungkin getDailyPembelianChart (lihat penjelasan bawah)
                ->chart($this->getDailyBarangChart($startOfMonth, $endOfMonth)) 
                ->chartColor('info')
        ];
    }

    // ---------------------------------------------------------------------------
    // HELPER FORMATTING (Untuk mempercantik tampilan angka)
    // ---------------------------------------------------------------------------

    // Menambah "Rp" dan titik ribuan
    protected function formatCurrency(int $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    // Hanya titik ribuan tanpa "Rp"
    protected function formatNumber(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    // ---------------------------------------------------------------------------
    // QUERY DATABASE (LOGIKA UTAMA)
    // ---------------------------------------------------------------------------

    // [RELASI-A] Implementasi Menghitung Total Uang Penjualan
    protected function sumTotalPenjualanForPeriod(Carbon $from, Carbon $to): int
    {
        // // [DEBUG] Cek range tanggal: dd($from, $to);
        
        return Penjualan::whereBetween('tanggal_penjualan', [$from, $to])
            ->with('items') // Eager loading relasi 'items' agar lebih cepat (mengurangi query N+1)
            ->get() // Ambil semua data (Hati-hati jika datanya ribuan, bisa berat!)
            // Koleksi PHP (Processing di level PHP, bukan Database)
            ->sum(fn ($penjualan) => $penjualan->items->sum(
                fn ($item) => (int) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0)
            ));
    }

    // [RELASI-B] Implementasi Menghitung Qty Barang Terjual
    protected function sumTotalQtyForPeriod(Carbon $from, Carbon $to): int
    {
        return Penjualan::whereBetween('tanggal_penjualan', [$from, $to])
            ->with('items')
            ->get()
            ->sum(fn ($penjualan) => $penjualan->items->sum(fn ($item) => (int) ($item->qty ?? 0)));
    }

    // [RELASI-E] Implementasi Menghitung Total Pembelian (Modal Keluar)
    protected function sumTotalPembelianForPeriod(Carbon $from, Carbon $to): int
    {
        $pembelians = Pembelian::whereBetween('created_at', [$from, $to]) 
            ->with('items') 
            ->get();
        
        return $pembelians->sum(function ($record) {
            return $record->items->sum(function ($item) {
                // Menghitung HPP * Qty
                return (int) ($item->hpp ?? 0) * (int) ($item->qty ?? 0);
            });
        });
    }

    // ---------------------------------------------------------------------------
    // CHART DATA GENERATORS (Untuk Grafik Garis Kecil)
    // ---------------------------------------------------------------------------

    // [RELASI-C] Generate Data Harian untuk Chart Pendapatan
    protected function getDailyPendapatanChart(Carbon $start, Carbon $end): array
    {
        // Membuat periode harian (looping dari tgl 1 s/d tgl 30/31)
        $period = CarbonPeriod::create($start, $end);

        return collect($period)
            ->map(fn (Carbon $date) => 
                // Memanggil ulang fungsi [RELASI-A] tapi hanya untuk 1 hari (startOfDay s/d endOfDay)
                $this->sumTotalPenjualanForPeriod($date->copy()->startOfDay(), $date->copy()->endOfDay())
            )
            ->values()
            ->all();
    }

    // [RELASI-D] Generate Data Harian untuk Chart Barang
    protected function getDailyBarangChart(Carbon $start, Carbon $end): array
    {
        $period = CarbonPeriod::create($start, $end);

        return collect($period)
            ->map(fn (Carbon $date) => 
                $this->sumTotalQtyForPeriod($date->copy()->startOfDay(), $date->copy()->endOfDay())
            )
            ->values()
            ->all();
    }

    // Fungsi ini sudah ada tapi belum dipanggil di Widget Pembelian (Widget 3)
    protected function getDailyPembelianChart(Carbon $start, Carbon $end): array
    {
        $period = CarbonPeriod::create($start, $end);

        return collect($period)
            ->map(fn (Carbon $date) => 
                $this->sumTotalPembelianForPeriod($date->copy()->startOfDay(), $date->copy()->endOfDay())
            )
            ->values()
            ->all();
    }
}
