<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Actions\StaticAction;

class ListPembelians extends ListRecords
{
    protected static string $resource = PembelianResource::class;

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
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }
}
