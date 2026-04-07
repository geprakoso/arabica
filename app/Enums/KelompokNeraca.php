<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KelompokNeraca: string implements HasLabel, HasColor
{
    case AsetLancar = 'aset_lancar';
    case AsetTidakLancar = 'aset_tidak_lancar';
    case LiabilitasJangkaPendek = 'liabilitas_jangka_pendek';
    case LiabilitasJangkaPanjang = 'liabilitas_jangka_panjang';
    case Ekuitas = 'ekuitas';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AsetLancar => 'Aset Lancar',
            self::AsetTidakLancar => 'Aset Tidak Lancar',
            self::LiabilitasJangkaPendek => 'Liabilitas Jangka Pendek',
            self::LiabilitasJangkaPanjang => 'Liabilitas Jangka Panjang',
            self::Ekuitas => 'Ekuitas',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::AsetLancar, self::AsetTidakLancar => 'success',
            self::LiabilitasJangkaPendek, self::LiabilitasJangkaPanjang => 'warning',
            self::Ekuitas => 'info',
        };
    }
}
