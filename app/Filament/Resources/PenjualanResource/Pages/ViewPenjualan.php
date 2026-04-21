<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Mail\InvoicePenjualanMail;
use App\Models\ProfilePerusahaan;
use App\Support\WebpUpload;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ViewPenjualan extends ViewRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Tombol Revert to Draft (hanya untuk final tanpa pembayaran real/bernilai)
        $hasRealPembayaran = $this->record->pembayaran()->where('jumlah', '>', 0)->exists();
        if ($this->record->isFinal() && ! $hasRealPembayaran) {
            $actions[] = Action::make('revert_to_draft')
                ->label('Ubah ke Draft')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('warning')
                ->modalHeading('Konfirmasi Ubah ke Draft')
                ->modalDescription('Anda akan mengubah transaksi FINAL menjadi DRAFT. Stok akan dikembalikan dan transaksi bisa diedit. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Ubah ke Draft')
                ->form([
                    Textarea::make('reason')
                        ->label('Alasan perubahan (opsional)')
                        ->rows(3)
                        ->placeholder('Contoh: Ada kesalahan input data'),
                ])
                ->action(function (array $data): void {
                    try {
                        // Reload record untuk memastikan data terbaru
                        $this->record->refresh();

                        // Log untuk debug
                        $hasRealPembayaran = $this->record->pembayaran()->where('jumlah', '>', 0)->exists();
                        \Illuminate\Support\Facades\Log::info('Revert to draft attempt', [
                            'penjualan_id' => $this->record->id_penjualan,
                            'no_nota' => $this->record->no_nota,
                            'status_dokumen' => $this->record->status_dokumen,
                            'has_pembayaran' => $this->record->pembayaran()->exists(),
                            'has_real_pembayaran' => $hasRealPembayaran,
                            'items_count' => $this->record->items()->count(),
                        ]);

                        $this->record->revertToDraft();

                        Notification::make()
                            ->title('Berhasil diubah ke Draft')
                            ->body("Transaksi {$this->record->no_nota} sekarang dalam mode draft. Stok telah dikembalikan.")
                            ->icon('heroicon-o-check-circle')
                            ->success()
                            ->send();

                        // Redirect ke edit page
                        $this->redirect(PenjualanResource::getUrl('edit', ['record' => $this->record]));

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Revert to draft failed', [
                            'penjualan_id' => $this->record->id_penjualan ?? null,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Gagal mengubah ke Draft')
                            ->body('Error: '.$e->getMessage())
                            ->icon('heroicon-o-exclamation-triangle')
                            ->danger()
                            ->send();
                    }
                });
        }

        // Upload foto - tersedia untuk semua
        $actions[] = Action::make('upload_dokumen')
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
                    ->disk('public')
                    ->visibility('public')
                    ->default(fn () => $this->record->foto_dokumen ?? [])
                    ->panelLayout('grid')
                    ->panelAspectRatio('1:1')
                    ->imagePreviewHeight('100')
                    ->directory('penjualan/dokumentasi')
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
                    ->body(count($data['foto'] ?? []).' foto ditambahkan')
                    ->success()
                    ->send();

                $this->refreshFormData(['foto_dokumen']);
            });

        // Edit action - hanya untuk draft
        if ($this->record->isDraft()) {
            $actions[] = EditAction::make();
        }

        // Invoice actions - hanya untuk final
        if ($this->record->isFinal()) {
            $actions[] = Action::make('invoice')
                ->label('Invoice')
                ->icon('heroicon-m-printer')
                ->color('primary')
                ->url(fn () => route('penjualan.invoice', $this->record))
                ->openUrlInNewTab();

            $actions[] = Action::make('invoice_simple')
                ->label('Invoice Simple')
                ->icon('heroicon-m-document-text')
                ->color('warning')
                ->url(fn () => route('penjualan.invoice.simple', $this->record))
                ->openUrlInNewTab();

            $actions[] = Action::make('email_invoice')
                ->label('Email Invoice')
                ->icon('heroicon-m-envelope')
                ->color('info')
                ->form([
                    Textarea::make('message_note')
                        ->label('Pesan untuk pelanggan')
                        ->rows(4)
                        ->placeholder('Terima kasih telah berbelanja di kami.'),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $memberEmail = $this->record->member?->email;

                    if (! $memberEmail) {
                        Notification::make()
                            ->danger()
                            ->title('Email pelanggan belum diisi.')
                            ->send();

                        return;
                    }

                    $penjualan = $this->record->load([
                        'items.produk',
                        'items.pembelianItem.pembelian',
                        'jasaItems.jasa',
                        'member',
                        'karyawan',
                        'akunTransaksi',
                        'pembayaran.akunTransaksi',
                    ]);
                    $profile = ProfilePerusahaan::first();

                    try {
                        $note = $data['message_note'] ?? null;
                        Mail::to($memberEmail)->send(new InvoicePenjualanMail($penjualan, $profile, $note));
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal mengirim invoice.')
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Invoice dikirim.')
                        ->body('Invoice dikirim ke '.$memberEmail)
                        ->send();
                });
        }

        return $actions;
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
