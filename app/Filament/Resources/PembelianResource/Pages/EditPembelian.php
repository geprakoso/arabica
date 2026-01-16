<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PenjualanResource;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

    public array $qtyLockedPenjualanReferences = [];
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
            $this->qtyLockedPenjualanReferences = $this->record->getBlockedPenjualanReferences()->all();
            $this->replaceMountedAction('qtyLocked');
            $this->halt(true);
        }
    }

    protected function qtyLockedAction(): Action
    {
        return Action::make('qtyLocked')
            ->modalHeading('Perubahan dibatalkan')
            ->modalDescription(fn () => $this->qtyLockedMessage ?? 'Qty pembelian tidak bisa diubah.')
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('warning')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn () => $this->buildPenjualanFooterActions($this->qtyLockedPenjualanReferences))
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
}
