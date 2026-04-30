<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Models\Pembelian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PembelianResource;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    public string $saveMode = 'draft';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Simpan Draft')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->action('saveDraft'),
            Action::make('saveFinal')
                ->label('Simpan Final')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action('saveFinal'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->formId('form')
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }

    public function saveDraft(): void
    {
        $this->saveMode = 'draft';
        $this->create();
    }

    public function saveFinal(): void
    {
        $this->saveMode = 'final';
        $this->create();
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
        // R16: Jika mode final, lock langsung setelah create
        if ($this->saveMode === 'final') {
            try {
                $this->record->lockFinal();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Gagal mengunci pembelian')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        $user = Auth::user();

        if (! $user) {
            return;
        }

        $modeLabel = $this->saveMode === 'final' ? 'Final' : 'Draft';

        Notification::make()
            ->title("Pembelian {$modeLabel} berhasil dibuat")
            ->body("No.PO {$this->record->no_po} ditambahkan inventory.")
            ->icon('heroicon-o-check-circle')
            ->actions([
                NotificationAction::make('Lihat')
                    ->url(PembelianResource::getUrl('view', ['record' => $this->record])),
            ])
            ->sendToDatabase($user);
    }
}
