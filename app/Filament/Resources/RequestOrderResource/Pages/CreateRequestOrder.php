<?php

namespace App\Filament\Resources\RequestOrderResource\Pages;

use App\Filament\Resources\RequestOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequestOrder extends CreateRecord
{
    protected static string $resource = RequestOrderResource::class;

    protected function getHeaderActions(): array
        {
            return [
                $this->getCreateFormAction()->formId('form'),
                ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
                $this->getCancelFormAction(),
            ];
        }

        protected function getFormActions(): array
        {
            return [];
        }
}
