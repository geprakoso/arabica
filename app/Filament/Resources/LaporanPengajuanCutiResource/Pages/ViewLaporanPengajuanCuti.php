<?php

namespace App\Filament\Resources\LaporanPengajuanCutiResource\Pages;

use App\Enums\StatusPengajuan;
use App\Filament\Resources\LaporanPengajuanCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanPengajuanCuti extends ViewRecord
{
    protected static string $resource = LaporanPengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status_pengajuan === StatusPengajuan::Pending)
                ->action(fn ($record) => $record->approveSubmission()),
            Actions\Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status_pengajuan === StatusPengajuan::Pending)
                ->action(fn ($record) => $record->rejectSubmission()),
        ];
    }
}
