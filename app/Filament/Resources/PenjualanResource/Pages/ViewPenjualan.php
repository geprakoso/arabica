<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjualan extends ViewRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invoice')
                ->label('Invoice')
                ->icon('heroicon-m-printer')
                ->color('primary')
                ->url(fn () => route('penjualan.invoice', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
