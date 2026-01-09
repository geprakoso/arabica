<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AtributCrosscheck\CrosscheckTable;
use App\Filament\Widgets\AtributCrosscheck\ListAplikasiTable;
use App\Filament\Widgets\AtributCrosscheck\ListGameTable;
use App\Filament\Widgets\AtributCrosscheck\ListOsTable;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class AtributCrosscheck extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string $view = 'filament.pages.atribut-crosscheck';

    protected static ?string $navigationLabel = 'Atribut Crosscheck';
    
    protected static ?string $title = 'Atribut Crosscheck';

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationParentItem = 'Penerimaan Service';

    public ?string $activeTab = 'crosscheck';

    protected $queryString = [
        'activeTab',
    ];

    public function mount(): void
    {
        // Default tab if not set
        if (! $this->activeTab) {
            $this->activeTab = 'crosscheck';
        }
    }

    public function updatedActiveTab($value)
    {
        $this->activeTab = $value;
    }

    protected function getHeaderWidgets(): array
    {
        return [
           // We will use the view to render widgets conditionally
        ];
    }
}
