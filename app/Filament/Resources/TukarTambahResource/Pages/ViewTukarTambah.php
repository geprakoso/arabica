<?php

namespace App\Filament\Resources\TukarTambahResource\Pages;

use App\Filament\Resources\TukarTambahResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewTukarTambah extends ViewRecord
{
    protected static string $resource = TukarTambahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Ubah')
                ->icon('heroicon-m-pencil')
                ->openUrlInNewTab(),
            Action::make('invoice')
                ->label('Invoice')
                ->icon('heroicon-m-printer')
                ->url(fn() => route('tukar-tambah.invoice', $this->record))
                ->openUrlInNewTab(),
            Action::make('invoice_simple')
                ->label('Invoice Simple')
                ->icon('heroicon-m-document-text')
                ->color('gray')
                ->url(fn() => route('tukar-tambah.invoice.simple', $this->record))
                ->openUrlInNewTab(),
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

                        Notification::make()
                            ->title('Gagal menghapus')
                            ->body($messages ?: 'Gagal menghapus tukar tambah.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
