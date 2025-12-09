<?php

namespace App\Filament\Resources\Akunting\KodeAkunResource\Pages;

use App\Filament\Resources\Akunting\KodeAkunResource;
use App\Filament\Pages\PengaturanAkunting;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateKodeAkun extends CreateRecord
{
    protected static string $resource = KodeAkunResource::class;

    protected function getRedirectUrl(): string
    {
        return PengaturanAkunting::getUrl(
            panel: Filament::getCurrentPanel()?->getId(),
            parameters: ['activeTab' => 'kode_akun']
        );
    }
}
