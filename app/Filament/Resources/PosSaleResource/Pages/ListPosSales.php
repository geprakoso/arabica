<?php

namespace App\Filament\Resources\PosSaleResource\Pages;

use App\Filament\Resources\PosSaleResource;
use Filament\Resources\Pages\Page;

class ListPosSales extends Page
{
    protected static string $resource = PosSaleResource::class;

    protected static string $view = 'filament.resources.pos-sale-resource.blank';

    public function mount(): void
    {
        $this->redirect(CreatePosSale::getUrl());
    }
}
