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
            Action::make('invoice_simple')
                ->label('Invoice Simple')
                ->icon('heroicon-m-document-text')
                ->color('gray')
                ->url(fn () => route('penjualan.invoice.simple', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
