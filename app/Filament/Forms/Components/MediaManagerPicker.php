<?php

namespace App\Filament\Forms\Components;

use TomatoPHP\FilamentMediaManager\Form\MediaManagerInput;

class MediaManagerPicker extends MediaManagerInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->schema([])
            ->defaultItems(0)
            ->maxItems(1)
            ->dehydrated(false);
    }
}
