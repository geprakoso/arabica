<?php

namespace App\Filament\Resources\RequestOrderResource\Pages;

use App\Models\RequestOrder;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\RequestOrderResource;

class CreateRequestOrder extends CreateRecord
{
    protected static string $resource = RequestOrderResource::class;

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Buat Permintaan')
            ->icon('heroicon-o-plus')
            ->color('primary')
        ;
    }


    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden()
        ;
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['no_nota'] = 'RO-' . now()->format('Ymd') . '-' . str_pad(static::getResource()::getModel()::count() + 1, 5, '0', STR_PAD_LEFT);

    //     return $data;
    // }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
