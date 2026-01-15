<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use App\Filament\Resources\PembelianResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;
use Filament\Actions\StaticAction;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;
    protected static ?String $title = 'Detail Pembelian';
    public ?string $deleteBlockedMessage = null;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah')
                ->icon('heroicon-m-pencil-square'),
            Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Pembelian')
                ->modalDescription('Pembelian yang masih dipakai transaksi lain akan diblokir.')
                ->action(function (): void {
                    try {
                        $this->record->delete();
                    } catch (ValidationException $exception) {
                        $messages = collect($exception->errors())
                            ->flatten()
                            ->implode(' ');

                        $this->deleteBlockedMessage = $messages ?: 'Gagal menghapus pembelian.';
                        $this->mountAction('deleteBlocked');
                        $this->halt(true);
                    }

                    $this->redirect(PembelianResource::getUrl('index'));
                }),
        ];
    }

    protected function deleteBlockedAction(): Action
    {
        return Action::make('deleteBlocked')
            ->modalHeading('Gagal menghapus')
            ->modalDescription(fn () => $this->deleteBlockedMessage ?? 'Gagal menghapus pembelian.')
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }
}
