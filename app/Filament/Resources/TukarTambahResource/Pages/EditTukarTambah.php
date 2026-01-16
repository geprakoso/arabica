<?php

namespace App\Filament\Resources\TukarTambahResource\Pages;

use App\Filament\Resources\TukarTambahResource;
use App\Filament\Resources\PenjualanResource;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\PembelianPembayaran;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use App\Models\PenjualanPembayaran;
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

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle')
                ->formId('form'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon('heroicon-m-x-mark')
                ->formId('form')
                ->color('danger'),
            Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Tukar Tambah')
                ->modalDescription('Tukar tambah yang masih dipakai transaksi lain akan diblokir.')
                ->action(function (): void {
                    try {
                        $this->record->delete();

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
                            'id_produk' => $item->id_produk,
                            'kondisi' => $kondisi,
                            'qty' => (int) ($item->qty ?? 0),
                            'harga_jual' => $hargaJual === null ? null : (int) $hargaJual,
                            'serials' => $serials,
                        ];
                    })
                    ->values()
                    ->all(),
                'jasa_items' => $penjualan->jasaItems
                    ->map(fn(PenjualanJasa $item): array => [
                        'jasa_id' => $item->jasa_id,
                        'qty' => (int) ($item->qty ?? 0),
                        'harga' => (int) ($item->harga ?? 0),
                    ])
                    ->values()
                    ->all(),
                'pembayaran' => $penjualan->pembayaran
                    ->map(fn(PenjualanPembayaran $item): array => [
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
                    ->map(fn(PembelianPembayaran $item): array => [
                        'metode_bayar' => $item->metode_bayar,
                        'akun_transaksi_id' => $item->akun_transaksi_id,
                        'jumlah' => (int) ($item->jumlah ?? 0),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $tanggal = array_key_exists('tanggal', $data) ? $data['tanggal'] : $record->tanggal;
            $catatan = array_key_exists('catatan', $data) ? $data['catatan'] : $record->catatan;
            $karyawanId = array_key_exists('id_karyawan', $data) ? $data['id_karyawan'] : $record->id_karyawan;

            $penjualanPayload = is_array($data['penjualan'] ?? null) ? $data['penjualan'] : [];
            $pembelianPayload = is_array($data['pembelian'] ?? null) ? $data['pembelian'] : [];

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
        $penjualan->items()->get()->each->delete();
        $penjualan->jasaItems()->get()->each->delete();
        $penjualan->pembayaran()->get()->each->delete();

        $this->createPenjualanItems($penjualan, $payload['items'] ?? []);
        $this->createPenjualanJasaItems($penjualan, $payload['jasa_items'] ?? []);
        $this->createPenjualanPembayaran($penjualan, $payload['pembayaran'] ?? []);

        $penjualan->recalculateTotals();
        $penjualan->recalculatePaymentStatus();
    }

    protected function syncPembelianDetails(Pembelian $pembelian, array $payload, ?int $penjualanId): void
    {
        $externalPenjualanNotas = $pembelian->items()
            ->whereHas('penjualanItems', function ($query) use ($penjualanId): void {
                if ($penjualanId) {
                    $query->where('id_penjualan', '!=', $penjualanId);
                }
            })
            ->with(['penjualanItems.penjualan'])
            ->get()
            ->flatMap(fn(PembelianItem $item) => $item->penjualanItems)
            ->filter(fn($item) => ! $penjualanId || (int) $item->id_penjualan !== $penjualanId)
            ->map(fn($item) => $item->penjualan?->no_nota)
            ->filter()
            ->unique()
            ->values();

        if ($externalPenjualanNotas->isNotEmpty()) {
            $notaList = $externalPenjualanNotas->implode(', ');

            throw ValidationException::withMessages([
                'pembelian.items' => 'Item pembelian sudah terpakai di transaksi lain. Edit tukar tambah diblokir. Nota: ' . $notaList . '.',
            ]);
        }

        $pembelian->items()->get()->each->delete();
        $pembelian->pembayaran()->get()->each->delete();

        $this->createPembelianItems($pembelian, $payload['items'] ?? []);
        $this->createPembelianPembayaran($pembelian, $payload['pembayaran'] ?? []);
    }

    protected function createPenjualanItems(Penjualan $penjualan, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

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

            DB::transaction(function () use ($penjualan, $productId, $qty, $customPrice, $condition, $serials): void {
                $this->fulfillPenjualanUsingFifo($penjualan, $productId, $qty, $customPrice, $condition, $serials);
            });
        }
    }

    protected function fulfillPenjualanUsingFifo(Penjualan $penjualan, int $productId, int $qty, ?int $customPrice, ?string $condition, array $serials): Collection
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $batchesQuery = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->orderBy('id_pembelian_item')
            ->lockForUpdate();

        if ($condition) {
            $batchesQuery->where('kondisi', $condition);
        }

        $batches = $batchesQuery->get();
        $available = (int) $batches->sum(fn(PembelianItem $batch): int => (int) ($batch->{$qtyColumn} ?? 0));

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Qty melebihi stok tersedia (' . $available . ').',
            ]);
        }

        $remaining = $qty;
        $created = collect();
        $serials = array_values($serials);

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $batchAvailable = (int) ($batch->{$qtyColumn} ?? 0);

            if ($batchAvailable <= 0) {
                continue;
            }

            $takeQty = min($remaining, $batchAvailable);
            $takeSerials = [];

            if (! empty($serials)) {
                $takeSerials = array_splice($serials, 0, $takeQty);
            }

            $record = PenjualanItem::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'id_produk' => $productId,
                'id_pembelian_item' => $batch->getKey(),
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
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => (int) $jumlah,
                'catatan' => $item['catatan'] ?? null,
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
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => (int) $jumlah,
                'catatan' => $item['catatan'] ?? null,
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
