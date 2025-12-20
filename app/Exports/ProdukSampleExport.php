<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProdukSampleExport implements WithMultipleSheets
{
    public function __construct(public array $data)
    {
    }

    public function sheets(): array
    {
        $payload = $this->data[0] ?? [];

        $produkRows = $payload['produk'] ?? [];
        $categoryRows = $payload['categories'] ?? [];
        $brandRows = $payload['brands'] ?? [];

        return [
            new ProdukTemplateSheet($produkRows),
            new ReferenceSheet('Kategori', ['id', 'nama_kategori'], $categoryRows),
            new ReferenceSheet('Brand', ['id', 'nama_brand'], $brandRows),
        ];
    }
}

class ProdukTemplateSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private array $rows)
    {
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return array_keys($this->rows[0] ?? [
            'nama_produk' => null,
            'kategori_id' => null,
            'kategori_nama' => null,
            'brand_id' => null,
            'brand_nama' => null,
            'sku' => null,
            'berat' => null,
            'panjang' => null,
            'lebar' => null,
            'tinggi' => null,
            'deskripsi' => null,
        ]);
    }

    public function title(): string
    {
        return 'Template Produk';
    }
}

class ReferenceSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private string $title,
        private array $headings,
        private array $rows
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
