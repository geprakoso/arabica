<?php

namespace App\Filament\Resources\TukarTambahResource\Pages;

use App\Filament\Resources\TukarTambahResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewTukarTambah extends ViewRecord
{
    protected static string $resource = TukarTambahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invoice')
                ->label('Invoice')
                ->icon('heroicon-m-printer')
                ->url(fn() => route('tukar-tambah.invoice', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
