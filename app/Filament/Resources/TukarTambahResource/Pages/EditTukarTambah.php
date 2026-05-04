<?php

namespace App\Filament\Resources\TukarTambahResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Filament\Resources\TukarTambahResource;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\PembelianPembayaran;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use App\Models\PenjualanPembayaran;
use App\Services\ValidationLogger;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditTukarTambah extends EditRecord
{
    protected static string $resource = TukarTambahResource::class;

    public array $deleteBlockedPenjualanReferences = [];

    public ?string $deleteBlockedMessage = null;

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle')
                ->formId('form')
                ->disabled(fn () => ! $record->canEditItems() && ! $record->canEditPayment()),
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon('heroicon-m-x-mark')
                ->formId('form')
                ->color('danger'),
            Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->visible(fn () => $record->canDelete())
                ->requiresConfirmation()
                ->modalHeading('Hapus Tukar Tambah')
                ->modalDescription('Tukar tambah yang masih dipakai transaksi lain akan diblokir.')
                ->action(function (): void {
                    try {
                        $this->record->delete();

                        // Tutup modal
                        $this->dispatch('close-modal', id: 'delete');

                        Notification::make()
                            ->title('Tukar tambah dihapus')
                            ->success()
                            ->send();

                        $this->redirect(TukarTambahResource::getUrl('index'));
                    } catch (ValidationException $exception) {
                        $messages = collect($exception->errors())
                            ->flatten()
                            ->implode(' ');

                        $this->deleteBlockedMessage = $messages ?: 'Gagal menghapus tukar tambah.';
                        $this->deleteBlockedPenjualanReferences = $this->record->getExternalPenjualanReferences()->all();

                        // Tutup modal terlebih dahulu
                        $this->dispatch('close-modal', id: 'delete');

                        // Notifikasi toast untuk delete blocked
                        Notification::make()
                            ->title('Tidak Bisa Dihapus')
                            ->body($this->deleteBlockedMessage)
                            ->icon('heroicon-o-exclamation-triangle')
                            ->danger()
                            ->persistent()
                            ->send();

                        $this->replaceMountedAction('deleteBlocked');
                    }
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function deleteBlockedAction(): Action
    {
        return Action::make('deleteBlocked')
            ->modalHeading('Gagal menghapus')
            ->modalDescription(fn () => $this->deleteBlockedMessage ?? 'Gagal menghapus tukar tambah.')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn () => $this->buildPenjualanFooterActions($this->deleteBlockedPenjualanReferences))
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
                $label = $nota ? 'Lihat '.$nota : 'Lihat Penjualan';

                return StaticAction::make('viewPenjualan'.$index)
                    ->button()
                    ->label($label)
                    ->url(PenjualanResource::getUrl('view', ['record' => $reference['id'] ?? 0]))
                    ->openUrlInNewTab()
                    ->color('danger');
            })
            ->values()
            ->all();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record->load([
            'penjualan.items.pembelianItem',
            'penjualan.jasaItems',
            'penjualan.pembayaran',
            'pembelian.items',
            'pembelian.pembayaran',
        ]);

        $penjualan = $record->penjualan;
        if ($penjualan) {
            $data['penjualan'] = [
                'id_member' => $penjualan->id_member,
                'id_karyawan' => $penjualan->id_karyawan,
                'no_nota' => $penjualan->no_nota,
                'diskon_total' => $penjualan->diskon_total ?? 0,
                'catatan' => $penjualan->catatan,
                'items' => $penjualan->items
                    ->map(function (PenjualanItem $item): array {
                        $hargaJual = $item->harga_jual;
                        if ($hargaJual === null) {
                            $hargaJual = $item->pembelianItem?->harga_jual;
                        }

                        $kondisi = $item->kondisi ?? $item->pembelianItem?->kondisi;
                        $serials = is_array($item->serials ?? null) ? $item->serials : [];

                        return [
                            'id_penjualan_item' => $item->id_penjualan_item,
                            'id_produk' => $item->id_produk,
                            'id_pembelian_item' => $item->id_pembelian_item, // Added
                            'hpp' => (int) ($item->hpp ?? 0), // Added
                            'kondisi' => $kondisi,
                            'qty' => (int) ($item->qty ?? 0),
                            'harga_jual' => $hargaJual === null ? null : (int) $hargaJual,
                            'serials' => $serials,
                        ];
                    })
                    ->values()
                    ->all(),
                'jasa_items' => $penjualan->jasaItems
                    ->map(fn (PenjualanJasa $item): array => [
                        'jasa_id' => $item->jasa_id,
                        'qty' => (int) ($item->qty ?? 0),
                        'harga' => (int) ($item->harga ?? 0),
                    ])
                    ->values()
                    ->all(),
                'pembayaran' => $penjualan->pembayaran
                    ->map(fn (PenjualanPembayaran $item): array => [
                        'metode_bayar' => $item->metode_bayar,
                        'akun_transaksi_id' => $item->akun_transaksi_id,
                        'jumlah' => (int) ($item->jumlah ?? 0),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        $pembelian = $record->pembelian;
        if ($pembelian) {
            $productColumn = PembelianItem::productForeignKey();

            $data['pembelian'] = [
                'id_supplier' => $pembelian->id_supplier,
                'id_karyawan' => $pembelian->id_karyawan,
                'no_po' => $pembelian->no_po,
                'tipe_pembelian' => $pembelian->tipe_pembelian,
                'catatan' => $pembelian->catatan,
                'items' => $pembelian->items
                    ->map(function (PembelianItem $item) use ($productColumn): array {
                        return [
                            'id_pembelian_item' => $item->getKey(),
                            'id_produk' => $item->{$productColumn},
                            'kondisi' => $item->kondisi ?? 'baru',
                            'qty' => (int) ($item->qty ?? 0),
                            'hpp' => (int) ($item->hpp ?? 0),
                            'harga_jual' => (int) ($item->harga_jual ?? 0),
                        ];
                    })
                    ->values()
                    ->all(),
                'pembayaran' => $pembelian->pembayaran
                    ->map(fn (PembelianPembayaran $item): array => [
                        'metode_bayar' => $item->metode_bayar,
                        'akun_transaksi_id' => $item->akun_transaksi_id,
                        'jumlah' => (int) ($item->jumlah ?? 0),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        // Populate unified_pembayaran
        $unifiedPayments = [];

        if ($penjualan) {
            foreach ($penjualan->pembayaran as $payment) {
                $unifiedPayments[] = [
                    'tipe_transaksi' => 'penjualan',
                    'tanggal' => $payment->tanggal,
                    'metode_bayar' => $payment->metode_bayar,
                    'akun_transaksi_id' => $payment->akun_transaksi_id,
                    'jumlah' => (int) $payment->jumlah,
                    'bukti_transfer' => $payment->bukti_transfer,
                    'catatan' => $payment->catatan,
                ];
            }
        }

        if ($pembelian) {
            foreach ($pembelian->pembayaran as $payment) {
                $unifiedPayments[] = [
                    'tipe_transaksi' => 'pembelian',
                    'tanggal' => $payment->tanggal,
                    'metode_bayar' => $payment->metode_bayar,
                    'akun_transaksi_id' => $payment->akun_transaksi_id,
                    'jumlah' => (int) $payment->jumlah,
                    'bukti_transfer' => $payment->bukti_transfer,
                    'catatan' => $payment->catatan,
                ];
            }
        }

        $data['unified_pembayaran'] = $unifiedPayments;

        // Populate id_member at root level for the form field
        if ($penjualan && $penjualan->id_member) {
            $data['id_member'] = $penjualan->id_member;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // VALIDASI: cek duplikat dan stok penjualan items
        $penjualanItems = $data['penjualan']['items'] ?? [];
        if (! empty($penjualanItems)) {
            $this->validatePenjualanItems($penjualanItems);
        }

        return $data;
    }

    /**
     * Validasi duplikat dan stok untuk penjualan items
     */
    protected function validatePenjualanItems(array $items): void
    {
        // Start batch logging
        ValidationLogger::startBatch();

        if (empty($items)) {
            // Log validation error
            ValidationLogger::logMinimumItems(
                sourceType: 'TukarTambah',
                sourceAction: 'update',
                minRequired: 1,
                currentCount: 0
            );

            throw ValidationException::withMessages([
                'penjualan.items' => 'Minimal harus ada 1 item produk.',
            ]);
        }

        // Cek duplikat produk - tidak boleh ada produk+batch+kondisi yang sama persis
        $productKeys = [];
        foreach ($items as $index => $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId > 0) {
                $key = $productId.'|'.($condition ?? '').'|'.$batchId;

                if (isset($productKeys[$key])) {
                    $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                    $batchInfo = $batchId > 0 ? ' (batch sama)' : '';
                    $conditionInfo = $condition ? " (kondisi: {$condition})" : '';
                    $errorMessage = "Produk '{$productName}'{$conditionInfo}{$batchInfo} sudah ada di baris {$productKeys[$key]}. Hapus duplikat di baris ".($index + 1).'.';

                    ValidationLogger::logDuplicate(
                        sourceType: 'TukarTambah',
                        sourceAction: 'update',
                        productName: $productName,
                        row: $index + 1,
                        inputData: [
                            'product_id' => $productId,
                            'batch_id' => $batchId,
                            'condition' => $condition,
                            'duplicate_row' => $productKeys[$key],
                            'current_row' => $index + 1,
                        ]
                    );

                    Notification::make()
                        ->title('Validasi Gagal - Duplikat Produk')
                        ->body($errorMessage)
                        ->icon('heroicon-o-exclamation-triangle')
                        ->danger()
                        ->persistent()
                        ->send();

                    throw ValidationException::withMessages([
                        'penjualan.items' => $errorMessage,
                    ]);
                }
                $productKeys[$key] = $index + 1;
            }
        }

        // Aggregate total qty per produk (dengan batch dan kondisi)
        $totalQtyMap = [];
        foreach ($items as $index => $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);
            $originalQty = (int) ($item['_original_qty'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $key = $productId.'|'.($condition ?? '').'|'.$batchId;

            if (! isset($totalQtyMap[$key])) {
                $totalQtyMap[$key] = [
                    'product_id' => $productId,
                    'condition' => $condition,
                    'batch_id' => $batchId,
                    'qty' => 0,
                    'original_qty' => 0,
                    'rows' => [],
                ];
            }
            $totalQtyMap[$key]['qty'] += $qty;
            $totalQtyMap[$key]['original_qty'] += $originalQty;
            $totalQtyMap[$key]['rows'][] = $index + 1;
        }

        // Validasi stok tersedia menggunakan StockBatch (sama seperti Penjualan standar)
        foreach ($totalQtyMap as $group) {
            $productId = $group['product_id'];
            $batchId = $group['batch_id'];
            $requestedQty = $group['qty'];
            $originalQty = $group['original_qty'];
            $rows = $group['rows'];

            $query = \App\Models\StockBatch::query()
                ->whereHas('pembelianItem', function ($q) use ($productId) {
                    $q->where('id_produk', $productId);
                })
                ->where('qty_available', '>', 0);

            if ($batchId > 0) {
                $query->where('pembelian_item_id', $batchId);
            }

            $availableQty = (int) $query->sum('qty_available');

            // Add back original qty if editing
            $availableQty += $originalQty;

            if ($availableQty < $requestedQty) {
                $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                $rowInfo = count($rows) > 1 ? ' (baris: '.implode(', ', $rows).')' : '';
                $errorMessage = "Stok tidak cukup untuk {$productName}{$rowInfo}. Tersedia: {$availableQty}, Dibutuhkan: {$requestedQty}";

                ValidationLogger::logStock(
                    sourceType: 'TukarTambah',
                    sourceAction: 'update',
                    productName: $productName,
                    available: $availableQty,
                    requested: $requestedQty,
                    inputData: [
                        'product_id' => $productId,
                        'batch_id' => $batchId,
                        'rows' => $rows,
                    ]
                );

                Notification::make()
                    ->title('Validasi Gagal - Stok Tidak Cukup')
                    ->body($errorMessage)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->danger()
                    ->persistent()
                    ->send();

                throw ValidationException::withMessages([
                    'penjualan.items' => $errorMessage,
                ]);
            }
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var \App\Models\TukarTambah $record */
        if ($record->is_locked) {
            throw ValidationException::withMessages([
                'status_dokumen' => 'Tukar Tambah sudah terkunci. Tidak bisa diubah.',
            ]);
        }

        return DB::transaction(function () use ($record, $data) {
            $tanggal = array_key_exists('tanggal', $data) ? $data['tanggal'] : $record->tanggal;
            $catatan = array_key_exists('catatan', $data) ? $data['catatan'] : $record->catatan;
            $karyawanId = array_key_exists('id_karyawan', $data) ? $data['id_karyawan'] : $record->id_karyawan;

            $penjualanPayload = is_array($data['penjualan'] ?? null) ? $data['penjualan'] : [];
            $pembelianPayload = is_array($data['pembelian'] ?? null) ? $data['pembelian'] : [];

            // Process unified payments and split them
            $unifiedPayments = $data['unified_pembayaran'] ?? [];
            $penjualanPayments = [];
            $pembelianPayments = [];

            foreach ($unifiedPayments as $payment) {
                if (! is_array($payment)) {
                    continue;
                }

                $tipeTransaksi = $payment['tipe_transaksi'] ?? null;

                // Remove tipe_transaksi from payment data before saving
                $paymentData = $payment;
                unset($paymentData['tipe_transaksi']);

                if ($tipeTransaksi === 'penjualan') {
                    $penjualanPayments[] = $paymentData;
                } elseif ($tipeTransaksi === 'pembelian') {
                    $pembelianPayments[] = $paymentData;
                }
            }

            // Override pembayaran arrays with unified payments
            $penjualanPayload['pembayaran'] = $penjualanPayments;
            $pembelianPayload['pembayaran'] = $pembelianPayments;

            $penjualan = $record->penjualan;
            if ($penjualan) {
                $penjualan->update([
                    'tanggal_penjualan' => $tanggal,
                    'catatan' => $penjualanPayload['catatan'] ?? $catatan,
                    'id_karyawan' => $penjualanPayload['id_karyawan'] ?? $karyawanId,
                    'id_member' => $penjualanPayload['id_member'] ?? $penjualan->id_member,
                    'diskon_total' => $penjualanPayload['diskon_total'] ?? $penjualan->diskon_total,
                    'no_nota' => $penjualanPayload['no_nota'] ?? $penjualan->no_nota,
                ]);

                $this->syncPenjualanDetails($penjualan, $penjualanPayload);
            }

            $pembelian = $record->pembelian;
            if ($pembelian) {
                $pembelian->update([
                    'tanggal' => $tanggal,
                    'catatan' => $pembelianPayload['catatan'] ?? $catatan,
                    'id_karyawan' => $pembelianPayload['id_karyawan'] ?? $karyawanId,
                    'id_supplier' => $pembelianPayload['id_supplier'] ?? $pembelian->id_supplier,
                    'no_po' => $pembelianPayload['no_po'] ?? $pembelian->no_po,
                    'tipe_pembelian' => $pembelianPayload['tipe_pembelian'] ?? $pembelian->tipe_pembelian,
                ]);

                $this->syncPembelianDetails($pembelian, $pembelianPayload, $penjualan?->getKey());
            }

            $record->update([
                'tanggal' => $tanggal,
                'catatan' => $catatan,
                'id_karyawan' => $karyawanId,
            ]);

            return $record;
        });
    }

    protected function syncPenjualanDetails(Penjualan $penjualan, array $payload): void
    {
        $tukarTambah = $penjualan->tukarTambah;
        $canEditItems = $tukarTambah?->canEditItems() ?? true;
        $canEditPayment = $tukarTambah?->canEditPayment() ?? true;

        if ($canEditItems) {
            $incomingItems = $payload['items'] ?? [];

            // Smart sync: compare existing vs incoming to avoid unnecessary delete-create cycles
            // yang menyebabkan double stock mutation (return + re-deduction)
            $existingItems = $penjualan->items()->get()->keyBy('id_penjualan_item');

            $incomingIds = collect($incomingItems)
                ->pluck('id_penjualan_item')
                ->filter()
                ->values()
                ->all();

            // 1. Hapus item yang tidak ada di form (user menghapus row)
            $toDelete = $existingItems->keys()->diff($incomingIds);
            foreach ($toDelete as $deleteId) {
                $existingItems[$deleteId]->delete(); // stok kembali via PenjualanItem::deleted event
            }

            // 2. Update existing items atau create baru
            foreach ($incomingItems as $itemData) {
                $itemId = $itemData['id_penjualan_item'] ?? null;

                if ($itemId && $existingItems->has($itemId)) {
                    // Update existing item
                    // PenjualanItem::updated event akan skip stock mutation jika batch+qty tidak berubah
                    $existingItem = $existingItems[$itemId];
                    $existingItem->update([
                        'id_produk' => $itemData['id_produk'],
                        'id_pembelian_item' => $itemData['id_pembelian_item'],
                        'qty' => $itemData['qty'],
                        'harga_jual' => ($itemData['harga_jual'] === '' || $itemData['harga_jual'] === null) ? null : (int) $itemData['harga_jual'],
                        'kondisi' => $itemData['kondisi'] ?? null,
                        'serials' => $itemData['serials'] ?? null,
                    ]);
                } else {
                    // Create new item (stok dikurangi via PenjualanItem::created event)
                    $productId = (int) ($itemData['id_produk'] ?? 0);
                    $qty = (int) ($itemData['qty'] ?? 0);
                    $batchId = (int) ($itemData['id_pembelian_item'] ?? 0);

                    if ($productId < 1 || $qty < 1) {
                        continue;
                    }

                    $customPrice = $itemData['harga_jual'] ?? null;
                    $customPrice = ($customPrice === '' || $customPrice === null) ? null : (int) $customPrice;
                    $condition = $itemData['kondisi'] ?? null;
                    $serials = is_array($itemData['serials'] ?? null) ? array_values($itemData['serials']) : [];

                    DB::transaction(function () use ($penjualan, $productId, $qty, $batchId, $customPrice, $condition, $serials): void {
                        if ($batchId > 0) {
                            $this->fulfillPenjualanWithBatch($penjualan, $productId, $qty, $batchId, $customPrice, $condition, $serials);
                        } else {
                            $this->fulfillPenjualanUsingFifo($penjualan, $productId, $qty, $customPrice, $condition, $serials);
                        }
                    });
                }
            }

            // Jasa items: tetap delete-recreate (tidak ada stock impact)
            $penjualan->jasaItems()->get()->each->delete();
            $this->createPenjualanJasaItems($penjualan, $payload['jasa_items'] ?? []);
        }

        if ($canEditPayment) {
            $penjualan->pembayaran()->get()->each->delete();
            $this->createPenjualanPembayaran($penjualan, $payload['pembayaran'] ?? []);
        }

        $penjualan->recalculateTotals();
        $penjualan->recalculatePaymentStatus();
    }

    protected function syncPembelianDetails(Pembelian $pembelian, array $payload, ?int $penjualanId): void
    {
        $tukarTambah = $pembelian->tukarTambah;
        $canEditPayment = $tukarTambah?->canEditPayment() ?? true;

        // Pembelian items SELALU locked di TT (mengikuti kebijakan Pembelian standar)
        // Tidak pernah bisa dihapus/dibuat ulang saat edit

        $externalPenjualanNotas = $pembelian->items()
            ->whereHas('penjualanItems', function ($query) use ($penjualanId): void {
                if ($penjualanId) {
                    $query->where('id_penjualan', '!=', $penjualanId);
                }
            })
            ->with(['penjualanItems.penjualan'])
            ->get()
            ->flatMap(fn (PembelianItem $item) => $item->penjualanItems)
            ->filter(fn ($item) => ! $penjualanId || (int) $item->id_penjualan !== $penjualanId)
            ->map(fn ($item) => $item->penjualan?->no_nota)
            ->filter()
            ->unique()
            ->values();

        if ($externalPenjualanNotas->isNotEmpty()) {
            // Jika item pembelian sudah dipakai di penjualan luar, hanya update pembayaran jika boleh
            if ($canEditPayment) {
                $pembelian->pembayaran()->get()->each->delete();
                $this->createPembelianPembayaran($pembelian, $payload['pembayaran'] ?? []);
            }

            return;
        }

        // Hanya update pembayaran, items tidak pernah dihapus (locked)
        if ($canEditPayment) {
            $pembelian->pembayaran()->get()->each->delete();
            $this->createPembelianPembayaran($pembelian, $payload['pembayaran'] ?? []);
        }
    }

    protected function createPenjualanItems(Penjualan $penjualan, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $customPrice = $item['harga_jual'] ?? null;
            $customPrice = ($customPrice === '' || $customPrice === null) ? null : (int) $customPrice;
            $condition = $item['kondisi'] ?? null;
            $serials = is_array($item['serials'] ?? null) ? array_values($item['serials']) : [];

            if (! empty($serials) && count($serials) !== $qty) {
                throw ValidationException::withMessages([
                    'penjualan.items' => 'Jumlah SN harus sama dengan Qty.',
                ]);
            }

            DB::transaction(function () use ($penjualan, $productId, $qty, $batchId, $customPrice, $condition, $serials): void {
                if ($batchId > 0) {
                    $this->fulfillPenjualanWithBatch($penjualan, $productId, $qty, $batchId, $customPrice, $condition, $serials);
                } else {
                    $this->fulfillPenjualanUsingFifo($penjualan, $productId, $qty, $customPrice, $condition, $serials);
                }
            });
        }
    }

    protected function fulfillPenjualanWithBatch(Penjualan $penjualan, int $productId, int $qty, int $batchId, ?int $customPrice, ?string $condition, array $serials): void
    {
        $stockBatch = \App\Models\StockBatch::query()
            ->where('pembelian_item_id', $batchId)
            ->lockForUpdate()
            ->first();

        if (! $stockBatch) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Batch stok tidak ditemukan.',
            ]);
        }

        if ($stockBatch->qty_available < $qty) {
            throw ValidationException::withMessages([
                'penjualan.items' => "Stok batch tidak cukup. Tersedia: {$stockBatch->qty_available}, Dibutuhkan: {$qty}",
            ]);
        }

        // Stock decrement is handled by PenjualanItem::created model event
        // Do NOT manually decrement here to avoid double-deduction

        $takeSerials = ! empty($serials) ? array_splice($serials, 0, $qty) : [];

        PenjualanItem::query()->create([
            'id_penjualan' => $penjualan->getKey(),
            'id_produk' => $productId,
            'id_pembelian_item' => $batchId,
            'qty' => $qty,
            'harga_jual' => $customPrice,
            'kondisi' => $stockBatch->pembelianItem?->kondisi ?? $condition,
            'serials' => empty($takeSerials) ? null : $takeSerials,
        ]);
    }

    protected function fulfillPenjualanUsingFifo(Penjualan $penjualan, int $productId, int $qty, ?int $customPrice, ?string $condition, array $serials): Collection
    {
        $batchesQuery = \App\Models\StockBatch::query()
            ->whereHas('pembelianItem', function ($q) use ($productId, $condition) {
                $q->where('id_produk', $productId);
                if ($condition) {
                    $q->where('kondisi', $condition);
                }
            })
            ->where('qty_available', '>', 0)
            ->orderBy('id')
            ->lockForUpdate();

        $batches = $batchesQuery->get();
        $available = (int) $batches->sum(fn (\App\Models\StockBatch $batch): int => $batch->qty_available);

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Qty melebihi stok tersedia ('.$available.').',
            ]);
        }

        $remaining = $qty;
        $created = collect();
        $serials = array_values($serials);

        foreach ($batches as $stockBatch) {
            if ($remaining <= 0) {
                break;
            }

            $batchAvailable = $stockBatch->qty_available;

            if ($batchAvailable <= 0) {
                continue;
            }

            $takeQty = min($remaining, $batchAvailable);
            $takeSerials = [];

            if (! empty($serials)) {
                $takeSerials = array_splice($serials, 0, $takeQty);
            }

            // Stock decrement is handled by PenjualanItem::created model event
            // Do NOT manually decrement here to avoid double-deduction

            $record = PenjualanItem::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'id_produk' => $productId,
                'id_pembelian_item' => $stockBatch->pembelian_item_id,
                'qty' => $takeQty,
                'harga_jual' => $customPrice,
                'kondisi' => $condition,
                'serials' => empty($takeSerials) ? null : $takeSerials,
            ]);

            $created->push($record);
            $remaining -= $takeQty;
        }

        return $created;
    }

    protected function createPembelianItems(Pembelian $pembelian, array $items): void
    {
        $productColumn = PembelianItem::productForeignKey();
        $qtyMasukColumn = PembelianItem::qtyMasukColumn();
        $qtySisaColumn = PembelianItem::qtySisaColumn();

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $data = [
                'id_pembelian' => $pembelian->getKey(),
                $productColumn => $productId,
                'qty' => $qty,
                'hpp' => (int) ($item['hpp'] ?? 0),
                'harga_jual' => (int) ($item['harga_jual'] ?? 0),
                'kondisi' => $item['kondisi'] ?? 'baru',
            ];

            if ($qtyMasukColumn !== 'qty') {
                $data[$qtyMasukColumn] = $qty;
            }

            if ($qtySisaColumn !== 'qty') {
                $data[$qtySisaColumn] = $qty;
            }

            PembelianItem::query()->create($data);
        }
    }

    protected function createPenjualanPembayaran(Penjualan $penjualan, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $metode = $item['metode_bayar'] ?? null;
            $jumlah = $item['jumlah'] ?? null;

            if (! $metode || $jumlah === null || $jumlah === '') {
                continue;
            }

            PenjualanPembayaran::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'tanggal' => $item['tanggal'] ?? now(),
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => (int) $jumlah,
                'catatan' => $item['catatan'] ?? null,
                'bukti_transfer' => $item['bukti_transfer'] ?? null,
            ]);
        }
    }

    protected function createPembelianPembayaran(Pembelian $pembelian, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $metode = $item['metode_bayar'] ?? null;
            $jumlah = $item['jumlah'] ?? null;

            if (! $metode || $jumlah === null || $jumlah === '') {
                continue;
            }

            PembelianPembayaran::query()->create([
                'id_pembelian' => $pembelian->getKey(),
                'tanggal' => $item['tanggal'] ?? now(),
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => (int) $jumlah,
                'catatan' => $item['catatan'] ?? null,
                'bukti_transfer' => $item['bukti_transfer'] ?? null,
            ]);
        }
    }

    protected function createPenjualanJasaItems(Penjualan $penjualan, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $jasaId = (int) ($item['jasa_id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 1);
            $harga = (int) ($item['harga'] ?? 0);

            if ($jasaId < 1 || $qty < 1) {
                continue;
            }

            PenjualanJasa::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'jasa_id' => $jasaId,
                'qty' => $qty,
                'harga' => $harga,
                'catatan' => $item['catatan'] ?? null,
            ]);
        }
    }
}
