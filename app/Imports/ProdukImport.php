<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProdukImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $kategoriId = $this->resolveKategori($row);
            $brandId = $this->resolveBrand($row);

            Produk::create([
                'nama_produk' => $row['nama_produk'] ?? null,
                'kategori_id' => $kategoriId,
                'brand_id' => $brandId,
                'sku' => $row['sku'] ?? null,
                'berat' => $row['berat'] ?? null,
                'panjang' => $row['panjang'] ?? null,
                'lebar' => $row['lebar'] ?? null,
                'tinggi' => $row['tinggi'] ?? null,
                'deskripsi' => $row['deskripsi'] ?? null,
            ]);
        }
    }

    protected function resolveKategori(Collection $row): ?int
    {
        $id = $row['kategori_id'] ?? null;
        $name = $this->cleanName($row['kategori_nama'] ?? null);

        if ($id) {
            return (int) $id;
        }

        if (!$name) {
            return null;
        }

        return Kategori::firstOrCreate(
            ['nama_kategori' => $name],
            ['slug' => Str::slug($name), 'is_active' => true]
        )->id;
    }

    protected function resolveBrand(Collection $row): ?int
    {
        $id = $row['brand_id'] ?? null;
        $name = $this->cleanName($row['brand_nama'] ?? null);

        if ($id) {
            return (int) $id;
        }

        if (!$name) {
            return null;
        }

        return Brand::firstOrCreate(
            ['nama_brand' => $name],
            ['slug' => Str::slug($name), 'is_active' => true]
        )->id;
    }

    protected function cleanName(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
