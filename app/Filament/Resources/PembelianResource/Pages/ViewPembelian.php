<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PenjualanResource;
use App\Models\Pembelian;
use App\Support\WebpUpload;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;

    protected static ?string $title = 'Detail Pembelian';

    public array $editBlockedPenjualanReferences = [];

    public array $deleteBlockedPenjualanReferences = [];

    public ?string $editBlockedMessage = null;

    public ?string $deleteBlockedMessage = null;

    public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return static::getModel()::with([
            'supplier',
            'karyawan',
            'tukarTambah',
            'requestOrders',
            'items.produk',
            'jasaItems.jasa',
            'pembayaran.akunTransaksi',
        ])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_dokumen')
                ->label('Upload Foto')
                ->icon('heroicon-m-camera')
                ->color('success')
                ->modalHeading('Upload Foto Dokumentasi')
                ->modalDescription('Upload foto nota, invoice, atau bukti penerimaan barang.')
                ->modalWidth('md')
                ->form([
                    FileUpload::make('foto')
                        ->label('Foto Dokumentasi')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->default(fn() => $this->record->foto_dokumen ?? [])
                        ->panelLayout('grid')
                        ->panelAspectRatio('1:1')
                        ->imagePreviewHeight('100')
                        ->disk('public')
                        ->visibility('public')
                        ->directory('pembelian/dokumentasi')
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('1920')
                        ->imageResizeTargetHeight('1080')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                            return WebpUpload::store($component, $file, 80);
                        })
                        ->required()
                        ->openable()
                        ->downloadable(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'foto_dokumen' => $data['foto'] ?? [],
                    ]);

                    Notification::make()
                        ->title('Foto berhasil diupload')
                        ->body(count($data['foto'] ?? []) . ' foto ditambahkan')
                        ->success()
                        ->send();

                    $this->refreshFormData(['foto_dokumen']);
                }),

            Action::make('edit')
                ->label('Ubah')
                ->icon('heroicon-m-pencil-square')
                ->visible(fn() => ! $this->record->is_locked)
                ->action(function (): void {
                    $this->redirect(PembelianResource::getUrl('edit', ['record' => $this->record]));
                }),

            Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->visible(fn() => auth()->user()?->hasRole('godmode'))
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Hapus Pembelian')
                ->modalDescription(function (): string {
                    $record = $this->record;

                    if (! $record->canDelete()) {
                        $reasons = [];

                        if (! empty($record->no_tt)) {
                            $reasons[] = '• Terikat Tukar Tambah: ' . $record->no_tt;
                        }

                        $notas = $record->getBlockedPenjualanReferences()->pluck('nota')->filter()->values();
                        if ($notas->isNotEmpty()) {
                            $reasons[] = '• Dipakai di penjualan: ' . $notas->implode(', ');
                        }

                        return implode("\n", $reasons) ?: 'Pembelian ini tidak dapat dihapus.';
                    }

                    return 'Apakah Anda yakin ingin menghapus pembelian **' . $record->no_po . '**? Langkah ini tidak dapat dibatalkan.';
                })
                ->modalSubmitActionLabel('Lanjutkan ke Langkah 2')
                ->modalCancelActionLabel('Batal')
                ->extraModalFooterActions(function (): array {
                    if ($this->record->canDelete()) {
                        return [];
                    }

                    return $this->record->getBlockedPenjualanReferences()
                        ->filter(fn(array $ref) => ! empty($ref['id']))
                        ->map(function (array $ref, int $index) {
                            $nota = $ref['nota'] ?? null;
                            $label = $nota ? 'Lihat ' . $nota : 'Lihat Penjualan';

                            return StaticAction::make('viewPenjualan' . $index)
                                ->button()
                                ->label($label)
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->url(PenjualanResource::getUrl('view', ['record' => $ref['id']]))
                                ->openUrlInNewTab()
                                ->color('warning');
                        })
                        ->values()
                        ->all();
                })
                ->action(function (): void {
                    if (! $this->record->canDelete()) {
                        $this->replaceMountedAction('deleteBlocked');
                        return;
                    }

                    $this->replaceMountedAction('deleteStep2');
                }),

            // Action::make('deleteBlocked')
            //     ->modalHeading('Tidak Bisa Dihapus')
            //     ->modalDescription(fn() => $this->deleteBlockedMessage ?? 'Gagal menghapus pembelian.')
            //     ->modalIcon('heroicon-o-exclamation-triangle')
            //     ->modalIconColor('danger')
            //     ->modalWidth('md')
            //     ->modalAlignment(Alignment::Center)
            //     ->modalFooterActions(fn() => $this->buildPenjualanFooterActions($this->deleteBlockedPenjualanReferences))
            //     ->modalFooterActionsAlignment(Alignment::Center)
            //     ->modalSubmitAction(false)
            //     ->modalCancelAction(fn(StaticAction $action) => $action->label('Tutup'))
            //     ->color('danger'),
        ];
    }

    public function deleteStep2Action(): Action
    {
        return Action::make('deleteStep2')
            ->modalHeading('⚠️ Dampak Penghapusan')
            ->modalDescription(function (): string {
                $record = $this->record;
                $desc = "**Anda akan menghapus pembelian {$record->no_po}.**\n\n";

                $itemsCount = $record->items()->count();
                if ($itemsCount > 0) {
                    $desc .= "**Item barang yang akan dihapus ({$itemsCount}):**\n";
                    foreach ($record->items->take(5) as $item) {
                        $productName = $item->produk?->nama_produk ?? 'Unknown';
                        $desc .= "- {$productName} (Qty: {$item->qty}, HPP: Rp " . number_format($item->hpp ?? 0, 0, ',', '.') . ")\n";
                    }
                    if ($itemsCount > 5) {
                        $desc .= '- dan ' . ($itemsCount - 5) . " item lainnya...\n";
                    }
                    $desc .= "\n";
                }

                $jasaCount = $record->jasaItems()->count();
                if ($jasaCount > 0) {
                    $desc .= "**Item jasa yang akan dihapus ({$jasaCount})**\n\n";
                }

                $paymentCount = $record->pembayaran()->count();
                if ($paymentCount > 0) {
                    $totalPayment = number_format($record->pembayaran->sum('jumlah'), 0, ',', '.');
                    $desc .= "**Data pembayaran yang akan dihapus ({$paymentCount}):**\n";
                    $desc .= "Total: Rp {$totalPayment}\n\n";
                }

                if ($record->tukarTambah()->exists()) {
                    $ttKode = $record->tukarTambah?->kode ?? 'TT-XXXXX';
                    $desc .= "**⚠️ PERINGATAN: Pembelian ini bagian dari Tukar Tambah ({$ttKode}).**\n";
                    $desc .= "Tukar Tambah akan kehilangan relasi pembelian-nya.\n\n";
                }

                $desc .= 'Apakah Anda yakin ingin melanjutkan?';

                return $desc;
            })
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalWidth('md')
            ->modalSubmitActionLabel('Lanjutkan ke Konfirmasi Password')
            ->modalCancelActionLabel('Batal')
            ->action(function (): void {
                $this->replaceMountedAction('deleteStep3');
            })
            ->color('warning');
    }

    public function deleteStep3Action(): Action
    {
        return Action::make('deleteStep3')
            ->modalHeading('🔐 Konfirmasi Password')
            ->modalDescription('Masukkan password Anda untuk mengkonfirmasi penghapusan. Tindakan ini tidak dapat dibatalkan.')
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('danger')
            ->modalWidth('md')
            ->form([
                \Filament\Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->placeholder('Masukkan password akun Anda'),
            ])
            ->modalSubmitActionLabel('🔥 Hapus Pembelian')
            ->modalCancelActionLabel('Batal')
            ->action(function (array $data): void {
                $user = auth()->user();

                if (! $user || ! Hash::check($data['password'], $user->password)) {
                    Notification::make()
                        ->title('Password salah')
                        ->body('Password yang Anda masukkan tidak sesuai.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $this->record->delete();
                } catch (ValidationException $exception) {
                    $messages = collect($exception->errors())
                        ->flatten()
                        ->implode(' ');

                    Notification::make()
                        ->title('Gagal menghapus')
                        ->body($messages ?: 'Terjadi kesalahan saat menghapus.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Pembelian berhasil dihapus')
                    ->success()
                    ->send();

                $this->redirect(PembelianResource::getUrl('index'));
            })
            ->color('danger');
    }

    protected function editBlockedAction(): Action
    {
        return Action::make('editBlocked')
            ->modalHeading('Tidak bisa edit')
            ->modalDescription(fn() => $this->editBlockedMessage ?? 'Pembelian tidak bisa diedit.')
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('warning')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn() => $this->buildPenjualanFooterActions($this->editBlockedPenjualanReferences))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }

    protected function buildPenjualanFooterActions(array $references): array
    {
        return collect($references)
            ->filter(fn(array $reference) => ! empty($reference['id']))
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
