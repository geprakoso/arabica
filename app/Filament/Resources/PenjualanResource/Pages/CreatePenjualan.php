<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Models\PembelianItem;
use App\Models\PenjualanItem;
use App\Filament\Resources\PenjualanResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Validation\ValidationException;

class CreatePenjualan extends CreateRecord
{
    protected static string $resource = PenjualanResource::class;

    protected static bool $canCreateAnother = false;

    protected array $itemsToCreate = [];

    protected function getRedirectUrl(): string
    {
        return PenjualanResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Penjualan berhasil disimpan.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract items_temp for manual processing after record creation
        if (isset($data['items_temp']) && is_array($data['items_temp'])) {
            $this->itemsToCreate = $data['items_temp'];
            unset($data['items_temp']);
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Process items with FIFO allocation
        if (!empty($this->itemsToCreate)) {
            $this->createItemsWithFifo($this->itemsToCreate);
        }

        // Recalculate totals
        $this->record->recalculateTotals();

        // Send notification
        $user = Auth::user();
        if ($user) {
            Notification::make()
                ->title('Penjualan baru dibuat')
                ->body("No. Nota {$this->record->no_nota} berhasil disimpan.")
                ->icon('heroicon-o-check-circle')
                ->actions([
                    Action::make('Lihat')
                        ->url(PenjualanResource::getUrl('view', ['record' => $this->record])),
                ])
                ->sendToDatabase($user);
        }
    }

    /**
     * Create items using FIFO batch allocation.
     */
    protected function createItemsWithFifo(array $items): void
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        foreach ($items as $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $customPrice = $item['harga_jual'] ?? null;
            $condition = $item['kondisi'] ?? null;
            $serials = is_array($item['serials'] ?? null) ? array_values($item['serials']) : [];

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            // Get available batches using FIFO (oldest first)
            $batchesQuery = PembelianItem::query()
                ->where($productColumn, $productId)
                ->where($qtyColumn, '>', 0)
                ->orderBy('id_pembelian_item')
                ->lockForUpdate();

            if ($condition) {
                $batchesQuery->where('kondisi', $condition);
            }

            $batches = $batchesQuery->get();
            $available = (int) $batches->sum(fn($batch) => (int) ($batch->{$qtyColumn} ?? 0));

            if ($available < $qty) {
                throw ValidationException::withMessages([
                    'items_temp' => "Stok tidak cukup untuk produk ini. Tersedia: {$available}, Dibutuhkan: {$qty}",
                ]);
            }

            $remaining = $qty;

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $batchAvailable = (int) ($batch->{$qtyColumn} ?? 0);

                if ($batchAvailable <= 0) {
                    continue;
                }

                $takeQty = min($remaining, $batchAvailable);

                // Split serials for this batch
                $takeSerials = [];
                if (!empty($serials)) {
                    $takeSerials = array_splice($serials, 0, $takeQty);
                }

                // Create PenjualanItem - model hooks will handle stock mutation
                PenjualanItem::create([
                    'id_penjualan' => $this->record->getKey(),
                    'id_produk' => $productId,
                    'id_pembelian_item' => $batch->id_pembelian_item,
                    'qty' => $takeQty,
                    'harga_jual' => $customPrice,
                    'kondisi' => $condition ?? $batch->kondisi,
                    'serials' => empty($takeSerials) ? null : $takeSerials,
                ]);

                $remaining -= $takeQty;
            }
        }
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            return parent::handleRecordCreation($data);
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()->formId('form'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}


