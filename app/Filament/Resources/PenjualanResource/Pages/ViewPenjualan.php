<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Mail\InvoicePenjualanMail;
use App\Models\Penjualan;
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
                        ->disk('public')
                        ->visibility('public')
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
                    $existingPhotos = $this->record->foto_dokumen ?? [];
                    $newPhotos = $data['foto'] ?? [];

                    // Merge existing photos with new ones
                    $allPhotos = array_merge($existingPhotos, $newPhotos);

                    $this->record->update([
                        'foto_dokumen' => $allPhotos,
                    ]);

                    Notification::make()
                        ->title('Foto berhasil diupload')
                        ->body(count($newPhotos).' foto ditambahkan')
                        ->success()
                        ->send();

                    $this->refreshFormData(['foto_dokumen']);
                }),
            EditAction::make(),
            Action::make('invoice')
                ->label('Invoice')
                ->icon('heroicon-m-printer')
                ->color('primary')
                ->url(fn () => route('penjualan.invoice', $this->record))
                ->openUrlInNewTab(),
            Action::make('invoice_simple')
                ->label('Invoice Simple')
                ->icon('heroicon-m-document-text')
                ->color('warning')
                ->url(fn () => route('penjualan.invoice.simple', $this->record))
                ->openUrlInNewTab(),
            Action::make('email_invoice')
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
                }),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
