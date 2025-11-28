<?php

namespace App\Livewire;

use App\Filament\Resources\PosSaleResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PosCartSummary extends StatsOverviewWidget
{
    public array $items = [];

    public float $discount = 0.0;

    protected int | string | array $columnSpan = 'full';

    public function mount(array $items = [], float | int $discount = 0): void
    {
        $this->items = $items;
        $this->discount = (float) $discount;
    }

    public function hydrate(): void
    {
        $this->cachedStats = null;
    }

    protected function getHeading(): ?string
    {
        return 'Ringkasan Keranjang';
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        [, $subtotal] = PosSaleResource::summarizeCart($this->items);

        $discount = min(max($this->discount, 0), $subtotal);
        $grandTotal = max(0, $subtotal - $discount);

        return [
            Stat::make('Subtotal', self::formatCurrency($subtotal)),
            Stat::make('Diskon', self::formatCurrency($discount))->color('warning'),
            Stat::make('Grand Total', self::formatCurrency($grandTotal))->color('success'),
        ];
    }

    protected static function formatCurrency(float $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}
