<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Mail\InvoicePenjualanMail;
use App\Models\Penjualan;
use App\Models\ProfilePerusahaan;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ViewPenjualan extends ViewRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil-square')
                ->color('primary')
                ->url(fn() => PenjualanResource::getUrl('edit', ['record' => $this->record])),
            Action::make('delete')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Penjualan $record): void {
                    $record->delete();
                }),
            Action::make('invoice')
                ->label('Invoice')
                ->icon('heroicon-m-printer')
                ->color('primary')
                ->url(fn() => route('penjualan.invoice', $this->record))
                ->openUrlInNewTab(),
            Action::make('invoice_simple')
                ->label('Invoice Simple')
                ->icon('heroicon-m-document-text')
                ->color('warning')
                ->url(fn() => route('penjualan.invoice.simple', $this->record))
                ->openUrlInNewTab(),
            Action::make('email_invoice')
                ->label('Email Invoice')
                ->icon('heroicon-m-envelope')
                ->color('success')
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
                        ->body('Invoice dikirim ke ' . $memberEmail)
                        ->send();
                }),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
