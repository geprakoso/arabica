<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum Keperluan: string implements HasLabel, HasColor
{
    case Libur = 'libur'; // Mengganti 'libur' jika maksudnya izin pribadi
    case Cuti = 'cuti';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Libur => 'Libur',
            self::Cuti => 'Cuti',
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