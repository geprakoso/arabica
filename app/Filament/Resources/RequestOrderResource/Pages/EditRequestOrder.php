<?php

namespace App\Filament\Resources\RequestOrderResource\Pages;

use App\Filament\Resources\RequestOrderResource;
use Filament\Resources\Pages\EditRecord;

class EditRequestOrder extends EditRecord
{
    protected static string $resource = RequestOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()->formId('form'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
