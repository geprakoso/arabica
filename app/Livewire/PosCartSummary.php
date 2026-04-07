<?php

namespace App\Livewire;

use App\Filament\Resources\PosSaleResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PosCartSummary extends StatsOverviewWidget
{
    public array $items = [];

    public array $services = [];

    public int $discount = 0;

    protected int | string | array $columnSpan = 'full';

    public function mount(array $items = [], array $services = [], int $discount = 0): void
    {
        $this->items = $items;
        $this->services = $services;
        $this->discount = (int) $discount;
    }
    /**
     * Reset cached stats on every request to ensure data freshness.
     */
    public function hydrate(): void
    {
        $this->cachedStats = null;
    }

    protected function getHeading(): ?string
    {
        return 'Ringkasan Keranjang';
    }

    /**
     * Calculate and return the stats for the cart summary.
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        [, $subtotal] = PosSaleResource::summarizeCart($this->items, $this->services);

        $discount = min(max($this->discount, 0), $subtotal);
        $grandTotal = max(0, $subtotal - $discount);

        return [
            Stat::make('Subtotal', self::formatCurrency($subtotal)),
            Stat::make('Diskon', self::formatCurrency($discount))->color('warning'),
            Stat::make('Grand Total', self::formatCurrency($grandTotal))->color('success'),
        ];
    }

    protected static function formatCurrency(int $value): string
    {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }
}
