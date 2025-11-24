<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum Keperluan: string implements HasLabel, HasColor
{
    case Cuti = 'cuti';
    case Libur = 'libur'; // Mengganti 'libur' jika maksudnya izin pribadi

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Cuti => 'Cuti Tahunan',
            self::Libur => 'Libur',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Cuti => 'info',
            self::Libur => 'danger',
        };
    }
}