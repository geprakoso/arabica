<?php

namespace App\Livewire;

use App\Enums\KategoriAkun;
use App\Models\InputTransaksiToko;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class LabaRugiBebanTable extends Component
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

            return InputTransaksiToko::query()
                ->with('jenisAkun')
                ->where('kategori_transaksi', KategoriAkun::Beban->value)
                ->whereBetween('tanggal_transaksi', [$start, $end])
                ->orderBy('tanggal_transaksi')
                ->get();
        });
    }

    public function render()
    {
        return view('livewire.laba-rugi-beban-table');
    }

    protected function cacheKey(): string
    {
        return 'laba-rugi:'.$this->monthKey.':beban';
    }
}
