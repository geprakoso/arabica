<?php

namespace App\Filament\Resources\Absensi\LiburCutiResource\Widgets;

use App\Enums\Keperluan;
use App\Enums\StatusPengajuan;
use App\Models\LiburCuti;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LiburCutiStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $activeYear = $this->getActiveYear();

        if (! $user) {
            return [
                Stat::make('Cuti Disetujui', '0')
                    ->description('Masuk untuk melihat data')
                    ->icon('heroicon-o-calendar'),
                Stat::make('Libur Disetujui', '0')
                    ->description('Masuk untuk melihat data')
                    ->icon('heroicon-o-sparkles'),
                Stat::make('Jadwal Berikutnya', '-')
                    ->description('Masuk untuk melihat data')
                    ->icon('heroicon-o-clock'),
            ];
        }

        $cutiCount = $this->countApproved($user, Keperluan::Cuti, $activeYear);
        $liburCount = $this->countApproved($user, Keperluan::Libur, $activeYear);
        $nextLeave = $this->findNextApprovedLeave($user);

        return [
            Stat::make('Cuti Disetujui', (string) $cutiCount)
                ->description("Total pengajuan cuti {$activeYear}")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('primary')
                ->icon('heroicon-o-clipboard-document-check'),
            Stat::make('Libur Disetujui', (string) $liburCount)
                ->description("Total pengajuan libur {$activeYear}")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->icon('heroicon-o-sparkles'),
            Stat::make('Jadwal Berikutnya', $nextLeave ? $this->formatPeriod($nextLeave) : 'Tidak ada')
                ->description($nextLeave && $nextLeave->keperluan
                    ? $nextLeave->keperluan->getLabel()
                    : 'Belum ada jadwal disetujui')
                ->descriptionIcon($nextLeave ? 'heroicon-o-calendar-days' : 'heroicon-o-information-circle')
                ->color($nextLeave ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),
        ];
    }

    private function countApproved(User $user, Keperluan $keperluan, int $year): int
    {
        return $this->baseApprovedQuery($user, $keperluan)
            ->where('user_id', $user->id)
            ->whereYear('mulai_tanggal', $year)
            ->count();
    }

    private function getActiveYear(): int
    {
        return Carbon::now()->year;
    }

    private function findNextApprovedLeave(User $user): ?LiburCuti
    {
        return LiburCuti::query()
            ->where('status_pengajuan', StatusPengajuan::Diterima)
            ->whereDate('mulai_tanggal', '>=', now()->startOfDay())
            ->where('user_id', $user->id)
            ->orderBy('mulai_tanggal')
            ->first();
    }

    private function formatPeriod(LiburCuti $record): string
    {
        $mulai = $record->mulai_tanggal;
        $sampai = $record->sampai_tanggal ?? $mulai;

        if (! $mulai) {
            return '-';
        }

        $startText = $mulai->format('d M Y');

        if (! $sampai || $sampai->equalTo($mulai)) {
            return $startText;
        }

        return $startText . ' - ' . $sampai->format('d M Y');
    }

    private function baseApprovedQuery(User $user, Keperluan $keperluan): Builder
    {
        return LiburCuti::query()
            ->where('keperluan', $keperluan)
            ->where('status_pengajuan', StatusPengajuan::Diterima)
            ->when(
                $user->hasRole('karyawan'),
                fn(Builder $query) => $query->where('user_id', $user->id),
            );
    }
}
