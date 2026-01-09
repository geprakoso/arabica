<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjadwalanService extends ViewRecord
{
    protected static string $resource = PenjadwalanServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('print')
                ->label('Cetak Invoice')
                ->icon('heroicon-m-printer')
                ->color('success')
                ->url(fn ($record) => route('penjadwalan-service.print', $record))
                ->openUrlInNewTab(),
            Actions\Action::make('print_crosscheck')
                ->label('Cetak Checklist')
                ->icon('heroicon-m-clipboard-document-check')
                ->color('info')
                ->visible(fn ($record) => $record->has_crosscheck)
                ->url(fn ($record) => route('penjadwalan-service.print-crosscheck', $record))
                ->openUrlInNewTab(),
        ];
    }
}
