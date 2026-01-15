<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

    public ?string $qtyLockedMessage = null;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-o-check-circle')
                ->formId('form'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon('heroicon-o-x-mark')
                ->formId('form')
                ->color('danger'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (ValidationException $exception) {
            $message = $exception->errors()['qty'][0] ?? 'Perubahan pembelian ditolak.';

            $this->qtyLockedMessage = $message;
            $this->mountAction('qtyLocked');
            $this->halt(true);
        }
    }

    protected function qtyLockedAction(): Action
    {
        return Action::make('qtyLocked')
            ->modalHeading('Perubahan dibatalkan')
            ->modalDescription(fn () => $this->qtyLockedMessage ?? 'Qty pembelian tidak bisa diubah.')
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }
}
