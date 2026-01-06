<?php

namespace App\Filament\Resources\Akunting\LaporanLabaRugiResource\Pages;

use App\Filament\Pages\LabaRugiCustom;
use App\Filament\Resources\Akunting\LaporanLabaRugiResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

/**
 * Page class for listing Laporan Laba Rugi (Income Statement).
 *
 * This class handles the logic for the list view of the Laporan Laba Rugi resource,
 * including tab management and redirection to a custom detail page.
 */
class ListLaporanLabaRugis extends ListRecords
{
    protected static string $resource = LaporanLabaRugiResource::class;

    /**
     * Get the header actions for the page.
     *
     * @return array
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Initialize the page.
     *
     * Checks if the active tab is 'detail' and resets it to 'bulanan' if so.
     * This prevents staying on the 'detail' tab state if navigating back or refreshing directly.
     *
     * @return void
     */
    public function mount(): void
    {
        parent::mount();

        if ($this->activeTab === 'detail') {
            $this->activeTab = 'bulanan';
        }
    }

    /**
     * Get the tabs available for this page.
     *
     * Defines two tabs: 'bulanan' (Monthly) and 'detail' (Detailed View).
     *
     * @return array
     */
    public function getTabs(): array
    {
        return [
            'bulanan' => Tab::make('Bulanan')
                ->icon('heroicon-m-calendar-days'),
            'detail' => Tab::make('Detail')
                ->icon('heroicon-m-document-text'),
        ];
    }

    /**
     * Hook to handle tab updates.
     *
     * Redirects to the custom LabaRugi page if the 'detail' tab is selected.
     *
     * @return void
     */
    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'detail') {
            $this->redirect(LabaRugiCustom::getUrl());

            return;
        }

        parent::updatedActiveTab();
    }
}
