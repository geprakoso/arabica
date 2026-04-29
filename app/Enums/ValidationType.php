<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ValidationType: string implements HasLabel, HasColor
{
    case Duplicate = 'duplicate';
    case Stock = 'stock';
    case Required = 'required';
    case Format = 'format';
    case BusinessRule = 'business_rule';
    case MinimumItems = 'minimum_items';
    case BatchNotFound = 'batch_not_found';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Duplicate => 'Duplikat',
            self::Stock => 'Stok',
            self::Required => 'Wajib Diisi',
            self::Format => 'Format',
            self::BusinessRule => 'Aturan Bisnis',
            self::MinimumItems => 'Minimum Item',
            self::BatchNotFound => 'Batch Tidak Ditemukan',
        };
    }

    public function getColor(): string|array|null
    {
        return 'warning';
    }
}