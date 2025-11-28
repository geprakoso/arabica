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
