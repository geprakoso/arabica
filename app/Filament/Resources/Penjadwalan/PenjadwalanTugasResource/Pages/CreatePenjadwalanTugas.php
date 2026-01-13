<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePenjadwalanTugas extends CreateRecord
{
    protected static string $resource = PenjadwalanTugasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Filament::auth()->id();

        if (isset($data['durasi_pengerjaan']) && in_array($data['durasi_pengerjaan'], ['1', '2', '3'])) {
            $days = (int) $data['durasi_pengerjaan'];
            $data['tanggal_mulai'] = now()->toDateString();
            $data['deadline'] = now()->addDays($days - 1)->toDateString();
        }

        return $data;
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         $this->getCreateFormAction(),
    //         $this->getCreateAnotherFormAction(),
    //         $this->getCancelFormAction(),
    //     ];
    // }

    // protected function getFormActions(): array
    // {
    //     // Pindahkan tombol ke header agar footer lebih bersih.
    //     return [];
    // }
}
