<?php

namespace App\Filament\Resources\MasterData\AkunTransaksiResource\Pages;

use App\Filament\Resources\MasterData\AkunTransaksiResource;
use App\Models\AkunTransaksi;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAkunTransaksi extends CreateRecord
{
    protected static string $resource = AkunTransaksiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $prefix = $this->buildPrefix($data);
        $data['kode_akun'] = $this->generateKodeAkun($prefix);

        return $data;
    }

    private function buildPrefix(array $data): string
    {
        $jenis = strtolower($data['jenis'] ?? '');

        return match ($jenis) {
            'transfer' => $this->prefixFromBank($data['nama_bank'] ?? null),
            'tunai' => $this->prefixFromWords($data['nama_akun'] ?? null, firstLetters: 3, secondLetters: 2, fallback: 5),
            'qris' => 'QRIS',
            default => $this->prefixFromWords($data['nama_akun'] ?? $data['jenis'] ?? null, firstLetters: 4, secondLetters: 0, fallback: 4),
        };
    }

    private function prefixFromBank(?string $namaBank): string
    {
        $clean = trim((string) $namaBank);

        if ($clean === '') {
            return 'TRSF';
        }

        $words = preg_split('/\s+/', strtoupper($clean));

        if (count($words) >= 2) {
            return substr($words[0], 0, 1) . substr($words[1], 0, 3);
        }

        return substr($words[0], 0, 4);
    }

    private function prefixFromWords(?string $input, int $firstLetters, int $secondLetters, int $fallback): string
    {
        $clean = preg_replace('/\s+/', ' ', trim((string) $input));
        $words = preg_split('/\s+/', strtoupper($clean));

        if (count($words) >= 2 && $secondLetters > 0) {
            return substr($words[0], 0, $firstLetters) . substr($words[1], 0, $secondLetters);
        }

        if (($words[0] ?? '') !== '') {
            return substr($words[0], 0, $fallback);
        }

        return 'AKUN';
    }

    private function generateKodeAkun(string $prefix): string
    {
        $lastKode = AkunTransaksi::where('kode_akun', 'like', $prefix . '%')
            ->orderByDesc('kode_akun')
            ->value('kode_akun');

        $nextNumber = 1;

        if ($lastKode) {
            $numericPart = (int) substr($lastKode, strlen($prefix));
            $nextNumber = $numericPart + 1;
        }

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
