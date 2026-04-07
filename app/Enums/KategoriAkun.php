<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KategoriAkun: string implements HasLabel, HasColor
{
    case Aktiva = 'aktiva';
    case Pasiva = 'pasiva';
    case Pendapatan = 'pendapatan';
    case Beban = 'beban';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Aktiva => 'Aktiva',
            self::Pasiva => 'Pasiva',
            self::Pendapatan => 'Pendapatan',
            self::Beban => 'Beban',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Aktiva => 'success',
            self::Pasiva => 'warning',
            self::Pendapatan => 'info',
            self::Beban => 'danger',
        };
    }
}
