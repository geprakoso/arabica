<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PenjualanResource;
use App\Models\Pembelian;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Actions;
use Filament\Actions\StaticAction;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

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
                ->label('Tambah Pembelian')
                ->icon('heroicon-s-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Pembelian::count())
                ->badgeColor('gray'),

            'draft' => Tab::make('Draft')
                ->icon('heroicon-o-pencil')
                ->badge(Pembelian::where('is_locked', false)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_locked', false)),

            'final' => Tab::make('Final')
                ->icon('heroicon-o-check-circle')
                ->badge(Pembelian::where('is_locked', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_locked', true)),
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