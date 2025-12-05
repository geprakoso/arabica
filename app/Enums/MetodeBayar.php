<?php

namespace App\Enums;

enum MetodeBayar: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case TRANSFER = 'transfer';
    case EWALLET = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CARD => 'Kartu',
            self::TRANSFER => 'Transfer',
            self::EWALLET => 'E-Wallet',
        };
    }

    public static function labels(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}

