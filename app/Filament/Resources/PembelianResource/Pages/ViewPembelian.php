<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use App\Filament\Resources\PembelianResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;
    protected static ?String $title = 'Detail Pembelian';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah')
                ->icon('heroicon-m-pencil-square'),
            DeleteAction::make(),
        ];
    }
}
