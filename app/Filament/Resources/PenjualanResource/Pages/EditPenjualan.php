<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Models\PembelianItem;
use App\Models\PenjualanItem;
use App\Filament\Resources\PenjualanResource;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPenjualan extends EditRecord
{
    protected static string $resource = PenjualanResource::class;

    protected array $itemsToCreate = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Transform existing items to items_temp format for the form
        $data['items_temp'] = collect($this->record->items)
            ->map(fn($item) => [
                'id_produk' => $item->id_produk,
                'kondisi' => $item->kondisi,
                'qty' => $item->qty,
                'harga_jual' => $item->harga_jual,
                'serials' => $item->serials ?? [],
            ])
            // Group by product and condition, sum qty and merge serials
            ->groupBy(fn($item) => $item['id_produk'] . '-' . ($item['kondisi'] ?? ''))
            ->map(function ($group) {
                $first = $group->first();
                // Merge all serials from items in this group
                $allSerials = $group->flatMap(fn($item) => $item['serials'] ?? [])->values()->toArray();
                return [
                    'id_produk' => $first['id_produk'],
                    'kondisi' => $first['kondisi'],
                    'qty' => $group->sum('qty'),
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
        // Extract items_temp for manual processing
        if (isset($data['items_temp']) && is_array($data['items_temp'])) {
            $this->itemsToCreate = $data['items_temp'];
            unset($data['items_temp']);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Delete existing items first (model hooks will restore stock)
        $this->record->items()->each(fn($item) => $item->delete());

        // Create new items with FIFO allocation
        if (!empty($this->itemsToCreate)) {
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

    protected function handleRecordUpdate(Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($record, $data) {
            return parent::handleRecordUpdate($record, $data);
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()->formId('form'),
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
}

