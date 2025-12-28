<?php

namespace App\Filament\Resources\LaporanLabaRugiResource\Pages;

use App\Filament\Pages\LabaRugiCustom;
use App\Filament\Resources\LaporanLabaRugiResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListLaporanLabaRugis extends ListRecords
{
    protected static string $resource = LaporanLabaRugiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(): void
    {
        parent::mount();

        if ($this->activeTab === 'detail') {
            $this->activeTab = 'bulanan';
        }
    }

    public function getTabs(): array
    {
        return [
            'bulanan' => Tab::make('Bulanan')
                ->icon('heroicon-m-calendar-days'),
            'detail' => Tab::make('Detail')
                ->icon('heroicon-m-document-text'),
        ];
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'detail') {
            $this->redirect(LabaRugiCustom::getUrl());

            return;
        }

        parent::updatedActiveTab();
    }
}
