<?php

namespace App\Filament\Resources\Absensi\AbsensiResource\Pages;

use App\Filament\Resources\Absensi\AbsensiResource;
use App\Models\Absensi;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        $userId = Auth::id();

        return [
            Actions\CreateAction::make()
                ->disabled(fn () => $userId
                    && Absensi::query()
                        ->where('user_id', $userId)
                        ->whereDate('tanggal', now())
                        ->exists()
                )
                // ->tooltip('Anda sudah absen hari ini'),
        ];
    }
}
