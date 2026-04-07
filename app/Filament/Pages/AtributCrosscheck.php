<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class AtributCrosscheck extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string $view = 'filament.pages.atribut-crosscheck';

    protected static ?string $navigationLabel = 'Atribut Crosscheck';

    protected static ?string $title = 'Atribut Crosscheck';

    protected static ?string $navigationGroup = 'Tugas';

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
