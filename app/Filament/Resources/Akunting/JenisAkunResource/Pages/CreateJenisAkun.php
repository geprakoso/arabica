<?php

namespace App\Filament\Resources\Akunting\JenisAkunResource\Pages;

use App\Filament\Resources\Akunting\JenisAkunResource;
use Filament\Actions;
use App\Filament\Pages\PengaturanAkunting;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateJenisAkun extends CreateRecord
{
    protected static string $resource = JenisAkunResource::class;

    protected function getRedirectUrl(): string
    {
        return PengaturanAkunting::getUrl(panel: Filament::getCurrentPanel()?->getId(), parameters: ['activeTab' => 'jenis_akun']);
    }
}
