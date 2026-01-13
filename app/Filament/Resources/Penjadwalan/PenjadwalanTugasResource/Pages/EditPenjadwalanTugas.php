<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Resources\Pages\EditRecord;

class EditPenjadwalanTugas extends EditRecord
{
    protected static string $resource = PenjadwalanTugasResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['durasi_pengerjaan']) && in_array($data['durasi_pengerjaan'], ['1', '2', '3'])) {
            $days = (int) $data['durasi_pengerjaan'];
            $data['tanggal_mulai'] = now()->toDateString();
            $data['deadline'] = now()->addDays($days - 1)->toDateString();
        }

        return $data;
    }
}
