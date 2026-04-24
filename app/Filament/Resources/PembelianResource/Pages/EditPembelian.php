<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PenjualanResource;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

    public array $qtyLockedPenjualanReferences = [];
    public ?string $qtyLockedMessage = null;

    /**
     * R16: Cek apakah record terkunci sebelum memuat halaman edit
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // R16: Redirect jika sudah locked
        if ($this->record->is_locked) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Data pembelian sudah dikunci dan tidak dapat diedit.')
                ->danger()
                ->send();
                
            $this->redirect(PembelianResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-o-check-circle')
                ->formId('form'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon('heroicon-o-x-mark')
                ->formId('form')
                ->color('danger'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function beforeSave(): void
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            
            $message = $errors['qty'][0] ?? 'Perubahan pembelian ditolak.';

            $this->qtyLockedMessage = $message;
            $this->qtyLockedPenjualanReferences = $this->record->getBlockedPenjualanReferences()->all();
            $this->replaceMountedAction('qtyLocked');
            $this->halt(true);
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

    protected function qtyLockedAction(): Action
    {
        return Action::make('qtyLocked')
            ->modalHeading('Perubahan dibatalkan')
            ->modalDescription(fn () => $this->qtyLockedMessage ?? 'Qty pembelian tidak bisa diubah.')
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('warning')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn () => $this->buildPenjualanFooterActions($this->qtyLockedPenjualanReferences))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }

    protected function buildPenjualanFooterActions(array $references): array
    {
        return collect($references)
            ->filter(fn (array $reference) => ! empty($reference['id']))
            ->map(function (array $reference, int $index) {
                $nota = $reference['nota'] ?? null;
                $label = $nota ? 'Lihat ' . $nota : 'Lihat Penjualan';

                return StaticAction::make('viewPenjualan' . $index)
                    ->button()
                    ->label($label)
                    ->url(PenjualanResource::getUrl('view', ['record' => $reference['id'] ?? 0]))
                    ->openUrlInNewTab()
                    ->color('danger');
            })
            ->values()
            ->all();
    }
}
