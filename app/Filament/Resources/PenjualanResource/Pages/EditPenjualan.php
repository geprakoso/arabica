<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use Filament\Actions\Action as HeaderAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditPenjualan extends EditRecord
{
    protected static string $resource = PenjualanResource::class;

    protected array $itemsToCreate = [];

    protected string $saveMode = 'final';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Transform existing items to items_temp format for the form
        $data['items_temp'] = collect($this->record->items)
            ->map(fn ($item) => [
                'id_produk' => $item->id_produk,
                'id_pembelian_item' => $item->id_pembelian_item,
                'kondisi' => $item->kondisi,
                'qty' => $item->qty,
                'hpp' => $item->hpp,
                'harga_jual' => $item->harga_jual,
                'serials' => $item->serials ?? [],
            ])
            // Group by product, condition, and batch, sum qty and merge serials
            ->groupBy(fn ($item) => $item['id_produk'].'-'.($item['kondisi'] ?? '').'-'.((int) ($item['id_pembelian_item'] ?? 0)))
            ->map(function ($group) {
                $first = $group->first();
                // Merge all serials from items in this group
                $allSerials = $group->flatMap(fn ($item) => $item['serials'] ?? [])->values()->toArray();

                return [
                    'id_produk' => $first['id_produk'],
                    'id_pembelian_item' => $first['id_pembelian_item'],
                    'kondisi' => $first['kondisi'],
                    'qty' => $group->sum('qty'),
                    'hpp' => $first['hpp'],
                    'harga_jual' => $first['harga_jual'],
                    'serials' => $allSerials,
                ];
            })
            ->values()
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Note: Fitur draft telah dihapus, semua penyimpanan langsung final
        // Extract items_temp for manual processing
        if (isset($data['items_temp']) && is_array($data['items_temp'])) {
            $this->itemsToCreate = $data['items_temp'];
            unset($data['items_temp']);
        }

        // Hapus status_dokumen jika ada (kolom ini akan dihapus dari database)
        unset($data['status_dokumen']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Delete existing items first (model hooks will restore stock)
        $this->record->items()->each(fn ($item) => $item->delete());

        // Create new items with FIFO allocation
        if (! empty($this->itemsToCreate)) {
            $this->createItemsWithFifo($this->itemsToCreate);
        }

        // Recalculate totals
        $this->record->recalculateTotals();
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
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);
            $serials = is_array($item['serials'] ?? null) ? array_values($item['serials']) : [];

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            if ($batchId > 0) {
                $batch = PembelianItem::query()
                    ->where($productColumn, $productId)
                    ->whereKey($batchId)
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw ValidationException::withMessages([
                        'items_temp' => 'Batch pembelian tidak ditemukan.',
                    ]);
                }

                $available = (int) ($batch->{$qtyColumn} ?? 0);

                if ($available < $qty) {
                    throw ValidationException::withMessages([
                        'items_temp' => "Stok batch tidak cukup. Tersedia: {$available}, Dibutuhkan: {$qty}",
                    ]);
                }

                $takeSerials = ! empty($serials) ? array_splice($serials, 0, $qty) : [];

                PenjualanItem::create([
                    'id_penjualan' => $this->record->getKey(),
                    'id_produk' => $productId,
                    'id_pembelian_item' => $batch->id_pembelian_item,
                    'qty' => $qty,
                    'harga_jual' => $customPrice,
                    'kondisi' => $batch->kondisi,
                    'serials' => empty($takeSerials) ? null : $takeSerials,
                ]);

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
            $available = (int) $batches->sum(fn ($batch) => (int) ($batch->{$qtyColumn} ?? 0));

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
                if (! empty($serials)) {
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

    protected function handleRecordUpdate(Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($record, $data) {
            return parent::handleRecordUpdate($record, $data);
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan Final')
                ->icon('heroicon-m-check')
                ->submit(null)
                ->action('saveFinal')
                ->formId('form'),
            HeaderAction::make('saveDraft')
                ->label('Simpan Draft')
                ->icon('heroicon-m-document')
                ->color('gray')
                ->submit(null)
                ->action('saveDraft')
                ->formId('form'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    public function saveFinal(): void
    {
        $this->saveMode = 'final';
        $this->save();
    }

    public function saveDraft(): void
    {
        $this->saveMode = 'draft';
        $this->save();
    }

    protected function guardDraftQtyLock(array $incomingItems): void
    {
        $existingQtyMap = $this->buildQtyMapFromExistingRecord($this->record);
        $incomingQtyMap = $this->buildQtyMapFromForm($incomingItems);

        foreach ($incomingQtyMap as $key => $incomingQty) {
            $existingQty = $existingQtyMap[$key] ?? 0;

            if (! isset($existingQtyMap[$key]) && $incomingQty > 0) {
                throw ValidationException::withMessages([
                    'items_temp' => 'Draft terkunci: tidak bisa menambah item produk baru.',
                ]);
            }

            if ($incomingQty > $existingQty) {
                throw ValidationException::withMessages([
                    'items_temp' => 'Draft terkunci: qty item produk tidak bisa ditambahkan.',
                ]);
            }
        }
    }

    protected function buildQtyMapFromExistingRecord(Penjualan $record): array
    {
        return $record->items
            ->groupBy(function (PenjualanItem $item): string {
                return implode('|', [
                    (int) $item->id_produk,
                    (string) ($item->kondisi ?? ''),
                    (int) ($item->id_pembelian_item ?? 0),
                ]);
            })
            ->map(fn ($group): int => (int) $group->sum('qty'))
            ->all();
    }

    protected function buildQtyMapFromForm(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $key = implode('|', [
                $productId,
                (string) ($item['kondisi'] ?? ''),
                (int) ($item['id_pembelian_item'] ?? 0),
            ]);

            $result[$key] = (int) ($result[$key] ?? 0) + $qty;
        }

        return $result;
    }
}
