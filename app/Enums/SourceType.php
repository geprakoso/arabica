<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SourceType: string implements HasLabel, HasColor
{
    case Penjualan = 'Penjualan';
    case Pembelian = 'Pembelian';
    case TukarTambah = 'TukarTambah';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Penjualan => 'Penjualan',
            self::Pembelian => 'Pembelian',
            self::TukarTambah => 'Tukar Tambah',
        };
    }

    public function getColor(): string|array|null
    {
        return 'primary';
    }
}