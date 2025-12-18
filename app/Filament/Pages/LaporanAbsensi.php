<?php

namespace App\Filament\Pages;

use App\Models\Absensi;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LaporanAbsensi extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Laporan Absensi';
    protected static ?string $title = 'Laporan Absensi';
    protected static ?string $navigationGroup = 'Absensi';
    protected static ?int $navigationSort = 30;
    protected static bool $shouldRegisterNavigation = true;
    protected static string $view = 'filament.pages.laporan-absensi';

    public ?string $mulaiTanggal = null;
    public ?string $sampaiTanggal = null;
    public ?string $statusFilter = null;
    public $karyawanFilter = null;

    protected $queryString = [
        'mulaiTanggal' => ['except' => null],
        'sampaiTanggal' => ['except' => null],
        'statusFilter' => ['except' => null],
        'karyawanFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        $this->mulaiTanggal ??= now()->startOfMonth()->toDateString();
        $this->sampaiTanggal ??= now()->endOfMonth()->toDateString();
    }

    public function updatedMulaiTanggal(?string $value): void
    {
        if ($value && $this->sampaiTanggal && $value > $this->sampaiTanggal) {
            $this->sampaiTanggal = $value;
        }
    }

    public function updatedSampaiTanggal(?string $value): void
    {
        if ($value && $this->mulaiTanggal && $value < $this->mulaiTanggal) {
            $this->mulaiTanggal = $value;
        }
    }

    public function updatedKaryawanFilter($value): void
    {
        $this->karyawanFilter = filled($value) ? (string) $value : null;
    }

    public function updatedStatusFilter($value): void
    {
        $this->statusFilter = filled($value) ? strtolower($value) : null;
    }

    public function resetFilters(): void
    {
        $this->mulaiTanggal = now()->startOfMonth()->toDateString();
        $this->sampaiTanggal = now()->endOfMonth()->toDateString();
        $this->statusFilter = null;
        $this->karyawanFilter = null;
    }

    protected function filteredAbsensiQuery(): Builder
    {
        return Absensi::query()
            ->when($this->mulaiTanggal, fn (Builder $query, string $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($this->sampaiTanggal, fn (Builder $query, string $date) => $query->whereDate('tanggal', '<=', $date))
            ->when(
                $this->karyawanFilter,
                fn (Builder $query, $userId) => $query->where('user_id', (int) $userId)
            )
            ->when(
                $this->statusFilter,
                fn (Builder $query, string $status) => $query->whereRaw('LOWER(status) = ?', [strtolower($status)])
            );
    }

    public function getStatusSummaryProperty(): array
    {
        $counts = $this->filteredAbsensiQuery()
            ->selectRaw('LOWER(status) as status_label')
            ->selectRaw('COUNT(*) as total')
            ->groupBy(DB::raw('LOWER(status)'))
            ->pluck('total', 'status_label')
            ->map(fn ($value) => (int) $value);

        $hadir = $counts->get('hadir', 0);
        $izin = $counts->get('izin', 0);
        $sakit = $counts->get('sakit', 0);
        $total = $counts->sum();
        $lainnya = max($total - ($hadir + $izin + $sakit), 0);

        return [
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'lainnya' => $lainnya,
            'total' => $total,
        ];
    }

    public function getUserSummariesProperty(): Collection
    {
        return $this->filteredAbsensiQuery()
            ->select('user_id')
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'hadir' THEN 1 ELSE 0 END) as total_hadir")
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'izin' THEN 1 ELSE 0 END) as total_izin")
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'sakit' THEN 1 ELSE 0 END) as total_sakit")
            ->selectRaw("SUM(CASE WHEN LOWER(status) NOT IN ('hadir','izin','sakit') THEN 1 ELSE 0 END) as total_lainnya")
            ->selectRaw('COUNT(*) as total_absen')
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderByDesc('total_absen')
            ->get();
    }

    public function getAbsensiRecordsProperty(): Collection
    {
        return $this->filteredAbsensiQuery()
            ->with('user:id,name')
            ->orderByDesc('tanggal')
            ->orderBy('user_id')
            ->get();
    }

    public function getEmployeeOptionsProperty(): array
    {
        return User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
