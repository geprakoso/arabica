<?php

namespace App\Livewire;

use App\Models\PembelianItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class LabaRugiPembelianTable extends Component
{
    use WithPagination;

    public ?string $monthKey = null;
    public int $perPage = 25;
    protected string $pageName = 'pembelianPage';
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

        return PembelianItem::query()
            ->with(['produk', 'pembelian.supplier'])
            ->whereHas('pembelian', fn ($query) => $query->whereBetween('tanggal', [$start, $end]))
            ->orderBy('id_pembelian_item')
            ->paginate($this->perPage, ['*'], $this->pageName);
    }

    public function getTotalHppProperty(): float
    {
        if (blank($this->monthKey)) {
            return 0.0;
        }

        return Cache::remember($this->cacheKey().':total', now()->addMinutes(10), function (): float {
            [$start, $end] = $this->monthRange();

            $total = PembelianItem::query()
                ->whereHas('pembelian', fn ($query) => $query->whereBetween('tanggal', [$start, $end]))
                ->selectRaw('SUM(COALESCE(hpp, 0) * COALESCE(qty, 0)) as total')
                ->value('total') ?? 0;

            return (float) $total;
        });
    }

    public function render()
    {
        return view('livewire.laba-rugi-pembelian-table');
    }

    protected function cacheKey(): string
    {
        return 'laba-rugi:'.$this->monthKey.':pembelian-items';
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
