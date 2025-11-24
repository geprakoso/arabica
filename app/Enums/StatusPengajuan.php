<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusPengajuan: string implements HasLabel, HasColor, HasIcon
{
    case Pending = 'pending';
    case Diterima = 'diterima';
    case Ditolak = 'reject';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Menunggu Konfirmasi',
            self::Diterima => 'Disetujui',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Pending => 'warning', // Kuning
            self::Diterima => 'success', // Hijau
            self::Ditolak => 'danger',  // Merah
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Diterima => 'heroicon-m-check-circle',
            self::Ditolak => 'heroicon-m-x-circle',
        };
    }
}