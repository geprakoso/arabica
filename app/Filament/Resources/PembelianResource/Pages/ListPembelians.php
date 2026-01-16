<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PenjualanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Actions\StaticAction;
use Filament\Support\Enums\Alignment;

class ListPembelians extends ListRecords
{
    protected static string $resource = PembelianResource::class;

    public array $editBlockedPenjualanReferences = [];
    public array $deleteBlockedPenjualanReferences = [];
    public ?string $editBlockedMessage = null;
    public ?string $deleteBlockedMessage = null;

    protected function getHeaderActions(): array
    {
        return [
                Actions\CreateAction::make()
                    ->label('Pembelian')
                    ->icon('heroicon-s-plus'),
        ];
    }

    protected function bulkDeleteBlockedAction(): Action
    {
        return Action::make('bulkDeleteBlocked')
            ->modalHeading('Sebagian gagal dihapus')
            ->modalDescription(fn () => $this->deleteBlockedMessage ?? 'Gagal menghapus pembelian.')
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

    protected function editBlockedAction(): Action
    {
        return Action::make('editBlocked')
            ->modalHeading('Tidak bisa edit')
            ->modalDescription(fn () => $this->editBlockedMessage ?? 'Pembelian tidak bisa diedit.')
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('warning')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn () => $this->buildPenjualanFooterActions($this->editBlockedPenjualanReferences))
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
