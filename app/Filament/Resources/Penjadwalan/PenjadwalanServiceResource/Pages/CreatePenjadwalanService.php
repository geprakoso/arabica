<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Breadcrumb;
use Filament\Support\Enums\BreadcrumbItem;

class CreatePenjadwalanService extends CreateRecord
{
    protected static string $resource = PenjadwalanServiceResource::class;
    protected static ?string $title = 'Tambah Penerimaan Service';

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        // Pindahkan tombol ke header agar footer lebih bersih.
        return [];
    }

    public function getBreadcrumb(): string
    {
        return static::$title ?? parent::getBreadcrumb();
    }
}
