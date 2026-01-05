<?php

namespace App\Filament\Resources\PosSaleResource\Pages;

use App\Filament\Resources\PosSaleResource;
use App\Services\POS\CheckoutPosAction;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePosSale extends CreateRecord
{
    protected static string $resource = PosSaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        PosSaleResource::ensureStockIsAvailable($data['items'] ?? []);
        PosSaleResource::ensureCartIsNotEmpty($data['items'] ?? [], $data['services'] ?? []);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $action = app(CheckoutPosAction::class);

        try {
            return $action->handle($data);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->errors());
            throw $e;
        }
    }

    
}
