<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPenjualan extends EditRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function handleRecordUpdate(Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($record, $data) {
            return parent::handleRecordUpdate($record, $data);
        });
    }
}
