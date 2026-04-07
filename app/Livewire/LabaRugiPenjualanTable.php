<?php

namespace App\Livewire;

use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class LabaRugiPenjualanTable extends Component
{
    use WithPagination;

    public ?string $monthKey = null;
    public int $perPage = 25;
    protected string $pageName = 'penjualanPage';
    protected string $paginationTheme = 'tailwind';

    public function mount(?string $monthKey = null): void
    {
        $this->monthKey = $monthKey;
    }

    public function getRowsProperty()
    {
        if (blank($this->monthKey)) {
            return new LengthAwarePaginator([], 0, $this->perPage, 1, [
                'pageName' => $this->pageName,
            ]);
        }

        [$start, $end] = $this->monthRange();

        return Penjualan::query()
            ->with(['member', 'items', 'jasaItems'])
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->orderBy('tanggal_penjualan')
            ->orderBy('id_penjualan')
            ->paginate($this->perPage, ['*'], $this->pageName);
    }

    public function getTotalPenjualanProperty(): float
    {
        if (blank($this->monthKey)) {
            return 0.0;
        }

        return Cache::remember($this->cacheKey().':total', now()->addMinutes(10), function (): float {
            [$start, $end] = $this->monthRange();

            $produkTotal = PenjualanItem::query()
                ->whereHas('penjualan', fn ($query) => $query->whereBetween('tanggal_penjualan', [$start, $end]))
                ->selectRaw('SUM(COALESCE(harga_jual, 0) * COALESCE(qty, 0)) as total')
                ->value('total') ?? 0;

            $jasaTotal = PenjualanJasa::query()
                ->whereHas('penjualan', fn ($query) => $query->whereBetween('tanggal_penjualan', [$start, $end]))
                ->selectRaw('SUM(COALESCE(harga, 0) * (CASE WHEN qty IS NULL OR qty < 1 THEN 1 ELSE qty END)) as total')
                ->value('total') ?? 0;

            return (float) $produkTotal + (float) $jasaTotal;
        });
    }

    public function render()
    {
        return view('livewire.laba-rugi-penjualan-table');
    }

    protected function cacheKey(): string
    {
        return 'laba-rugi:'.$this->monthKey.':penjualan';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function monthRange(): array
    {
        $date = Carbon::createFromFormat('Y-m', $this->monthKey);

        return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }
}
