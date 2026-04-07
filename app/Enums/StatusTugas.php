<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusTugas: string implements HasLabel, HasColor, HasIcon
{
    case Pending = 'pending';
    case Proses = 'proses';
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Proses => 'Proses',
            self::Selesai => 'Selesai',
            self::Batal => 'Batal',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Proses => 'info',
            self::Selesai => 'success',
            self::Batal => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Proses => 'heroicon-m-cog-6-tooth',
            self::Selesai => 'heroicon-m-check-circle',
            self::Batal => 'heroicon-m-x-circle',
        };
    }
}
