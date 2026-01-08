<?php

namespace App\Filament\Resources\TukarTambahResource\Pages;

use App\Filament\Resources\TukarTambahResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTukarTambah extends EditRecord
{
    protected static string $resource = TukarTambahResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $tanggal = $data['tanggal'] ?? $record->tanggal;
            $catatan = $data['catatan'] ?? null;
            $karyawanId = $data['id_karyawan'] ?? null;

            $record->penjualan?->update([
                'tanggal_penjualan' => $tanggal,
                'catatan' => $catatan,
                'id_karyawan' => $karyawanId,
            ]);

            $record->pembelian?->update([
                'tanggal' => $tanggal,
                'catatan' => $catatan,
                'id_karyawan' => $karyawanId,
            ]);

            $record->update([
                'tanggal' => $tanggal,
                'catatan' => $catatan,
                'id_karyawan' => $karyawanId,
            ]);

            return $record;
        });
    }
}
