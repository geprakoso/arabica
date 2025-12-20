<?php

namespace App\Filament\Resources\MasterData\ProdukResource\Pages;

use App\Exports\ProdukSampleExport;
use App\Filament\Resources\MasterData\ProdukResource;
use App\Imports\ProdukImport;
use App\Models\Brand;
use App\Models\Kategori;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListProduks extends ListRecords
{
    protected static string $resource = ProdukResource::class;

    protected function getHeaderActions(): array
    {
        $categories = Kategori::select('id', 'nama_kategori')->orderBy('nama_kategori')->get();
        $brands = Brand::select('id', 'nama_brand')->orderBy('nama_brand')->get();

        return [
            ExcelImportAction::make()
                ->color('primary')
                ->label('Import Excel')
                ->use(ProdukImport::class)
                ->sampleExcel(
                    sampleData: $this->makeSampleData($categories, $brands),
                    fileName: 'sample-produk.xlsx',
                    exportClass: ProdukSampleExport::class,
                    sampleButtonLabel: 'Unduh Template',
                    customiseActionUsing: fn(FormAction $action) => $action
                        ->color('secondary')
                        ->icon('heroicon-m-clipboard')
                        ->requiresConfirmation(),
                ),
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('Tambah Produk'),
        ];
    }

    protected function makeSampleData(Collection $categories, Collection $brands): array
    {
        $sampleRows = [
            $this->makeSampleRow(
                namaProduk: 'CPU Intel Core i7-14700K',
                kategori: $categories->get(0),
                brand: $brands->get(0),
                sku: 'MDP0001',
                berat: 120,
                dimensi: [10, 10, 8],
                deskripsi: '20 core / 28 thread, boost 5.6GHz, socket LGA1700, TDP 125W'
            ),
            $this->makeSampleRow(
                namaProduk: 'GPU NVIDIA RTX 4070 Super',
                kategori: $categories->get(1) ?? $categories->get(0),
                brand: $brands->get(1) ?? $brands->get(0),
                sku: 'MDP0002',
                berat: 1100,
                dimensi: [30, 12, 5],
                deskripsi: '12GB GDDR6X, boost clock 2.48GHz, 220W TDP, 2x 8-pin'
            ),
        ];

        $categoryRows = $categories
            ->map(fn (Kategori $kategori) => [
                'id' => $kategori->id,
                'nama_kategori' => $kategori->nama_kategori,
            ])
            ->values()
            ->all();

        if (empty($categoryRows)) {
            $categoryRows[] = [
                'id' => '',
                'nama_kategori' => 'Tambahkan kategori terlebih dahulu',
            ];
        }

        $brandRows = $brands
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'nama_brand' => $brand->nama_brand,
            ])
            ->values()
            ->all();

        if (empty($brandRows)) {
            $brandRows[] = [
                'id' => '',
                'nama_brand' => 'Tambahkan brand terlebih dahulu',
            ];
        }

        return [[
            'produk' => $sampleRows,
            'categories' => $categoryRows,
            'brands' => $brandRows,
        ]];
    }

    protected function makeSampleRow(
        string $namaProduk,
        ?Kategori $kategori,
        ?Brand $brand,
        string $sku,
        float|int $berat,
        array $dimensi,
        string $deskripsi
    ): array {
        [$panjang, $lebar, $tinggi] = $dimensi + [null, null, null];

        return [
            'nama_produk' => $namaProduk,
            'kategori_id' => $kategori?->id,
            'kategori_nama' => $kategori?->nama_kategori ?? 'Isi nama kategori',
            'brand_id' => $brand?->id,
            'brand_nama' => $brand?->nama_brand ?? 'Isi nama brand',
            'sku' => $sku,
            'berat' => $berat,
            'panjang' => $panjang,
            'lebar' => $lebar,
            'tinggi' => $tinggi,
            'deskripsi' => $deskripsi,
        ];
    }
}
