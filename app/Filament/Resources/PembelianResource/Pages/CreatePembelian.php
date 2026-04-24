<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Models\Pembelian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PembelianResource;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Buat')
                ->icon('heroicon-o-plus')
                ->formId('form'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->formId('form')
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pembelian berhasil dibuat. Silakan tambah produk melalui tabel di bawah.';
    }

    protected function beforeCreate(): void
    {
        // R02: Validasi duplikat produk+kondisi sebelum simpan
        $items = $this->data['items'] ?? [];
        $combinations = [];

        foreach ($items as $item) {
            $produkId = $item['id_produk'] ?? null;
            $kondisi = $item['kondisi'] ?? null;

            if (empty($produkId)) {
                continue;
            }

            $key = "{$produkId}|{$kondisi}";

            if (in_array($key, $combinations)) {
                $produkName = \App\Models\Produk::find($produkId)?->nama_produk ?? 'Produk';

                Notification::make()
                    ->title('Duplikasi Item Terdeteksi')
                    ->body("Produk **{$produkName}** dengan kondisi **" . ucfirst($kondisi ?? '-') . "** sudah ada di daftar. Gabungkan qty-nya atau ubah kondisi.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }

            $combinations[] = $key;
        }
    }

    protected function handleRecordCreation(array $data): Pembelian
    {
        try {
            // Remove no_po from data to let the model's booted() event generate a unique one
            unset($data['no_po']);

            return parent::handleRecordCreation($data);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Fallback: tangkap error database unique constraint
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Terjadi duplikasi data. Silakan periksa kembali item barang.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Pembelian baru dibuat')
            ->body("No.PO {$this->record->no_po} ditambahkan inventory.")
            ->icon('heroicon-o-check-circle')
            ->actions([
                Action::make('Lihat')
                    ->url(PembelianResource::getUrl('edit', ['record' => $this->record])),
            ])
            ->sendToDatabase($user);
    }
}
