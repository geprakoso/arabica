<?php

namespace App\Livewire;

use App\Enums\KategoriAkun;
use App\Models\InputTransaksiToko;
use App\Models\JenisAkun;
use App\Models\KodeAkun;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class LabaRugiBebanTable extends Component
{
    use WithPagination;

    public ?string $monthKey = null;
    public int $perPage = 25;
    protected string $pageName = 'bebanPage';
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

        $date = $this->monthDate();
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $jenisAkunTable = (new JenisAkun())->getTable();
        $kodeAkunTable = (new KodeAkun())->getTable();

        return InputTransaksiToko::query()
            ->select("{$transaksiTable}.*")
            ->leftJoin($jenisAkunTable, "{$jenisAkunTable}.id", '=', "{$transaksiTable}.kode_jenis_akun_id")
            ->leftJoin($kodeAkunTable, "{$kodeAkunTable}.id", '=', "{$jenisAkunTable}.kode_akun_id")
            ->with('jenisAkun')
            ->whereRaw(
                "LOWER(COALESCE({$transaksiTable}.kategori_transaksi, {$kodeAkunTable}.kategori_akun)) = ?",
                [KategoriAkun::Beban->value]
            )
            ->whereYear('tanggal_transaksi', $date->year)
            ->whereMonth('tanggal_transaksi', $date->month)
            ->orderBy('tanggal_transaksi')
            ->paginate($this->perPage, ['*'], $this->pageName);
    }

    public function getTotalNominalProperty(): float
    {
        if (blank($this->monthKey)) {
            return 0.0;
        }

        $date = $this->monthDate();
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $jenisAkunTable = (new JenisAkun())->getTable();
        $kodeAkunTable = (new KodeAkun())->getTable();

        $total = InputTransaksiToko::query()
            ->leftJoin($jenisAkunTable, "{$jenisAkunTable}.id", '=', "{$transaksiTable}.kode_jenis_akun_id")
            ->leftJoin($kodeAkunTable, "{$kodeAkunTable}.id", '=', "{$jenisAkunTable}.kode_akun_id")
            ->whereRaw(
                "LOWER(COALESCE({$transaksiTable}.kategori_transaksi, {$kodeAkunTable}.kategori_akun)) = ?",
                [KategoriAkun::Beban->value]
            )
            ->whereYear('tanggal_transaksi', $date->year)
            ->whereMonth('tanggal_transaksi', $date->month)
            ->sum('nominal_transaksi');

        return (float) $total;
    }

    /**
     * @return Carbon
     */
    protected function monthDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->monthKey);
    }

    public function render()
    {
        return view('livewire.laba-rugi-beban-table');
    }
}
