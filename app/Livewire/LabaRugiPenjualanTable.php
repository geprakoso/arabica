<?php

namespace App\Livewire;

use App\Models\PenjualanItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class LabaRugiPenjualanTable extends Component
{
    public ?string $monthKey = null;

    public function mount(?string $monthKey = null): void
    {
        $this->monthKey = $monthKey;
    }

    public function getRowsProperty(): Collection
    {
        if (blank($this->monthKey)) {
            return collect();
        }

        return Cache::remember($this->cacheKey(), now()->addMinutes(10), function (): Collection {
            $date = Carbon::createFromFormat('Y-m', $this->monthKey);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            return PenjualanItem::query()
                ->with(['produk', 'penjualan'])
                ->whereHas('penjualan', fn ($query) => $query->whereBetween('tanggal_penjualan', [$start, $end]))
                ->orderBy('id_penjualan_item')
                ->get();
        });
    }

    public function render()
    {
        return view('livewire.laba-rugi-penjualan-table');
    }

    protected function cacheKey(): string
    {
        return 'laba-rugi:'.$this->monthKey.':penjualan-items';
    }
}
