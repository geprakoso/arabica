<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjadwalanService extends ViewRecord
{
    protected static string $resource = PenjadwalanServiceResource::class;

    protected function getHeaderActions(): array
    {
        $statusActions = collect([
            'pending' => ['Menunggu Antrian', 'gray', 'heroicon-m-clock'],
            'diagnosa' => ['Sedang Diagnosa', 'info', 'heroicon-m-beaker'],
            'waiting_part' => ['Menunggu Sparepart', 'warning', 'heroicon-m-wrench-screwdriver'],
            'progress' => ['Sedang Dikerjakan', 'info', 'heroicon-m-cog-6-tooth'],
            'done' => ['Selesai (Siap Ambil)', 'success', 'heroicon-m-check-circle'],
            'cancel' => ['Dibatalkan', 'danger', 'heroicon-m-x-circle'],
        ])->map(function (array $meta, string $status): Actions\Action {
            return Actions\Action::make('status_' . $status)
                ->label($meta[0])
                ->color($meta[1])
                ->icon($meta[2])
                ->visible(fn () => $this->getRecord()->status !== $status)
                ->action(function () use ($status): void {
                    $record = $this->getRecord();
                    $record->update(['status' => $status]);

                    Notification::make()
                        ->title('Status diperbarui')
                        ->success()
                        ->send();
                });
        })->all();

        return [
            Actions\ActionGroup::make($statusActions)
                ->label('Status')
                ->icon('heroicon-m-adjustments-horizontal')
                ->tooltip('Ubah status cepat')
                ->button(),
            Actions\EditAction::make(),
            Actions\Action::make('print')
                ->label('Cetak Invoice')
                ->icon('heroicon-m-printer')
                ->color('success')
                ->url(fn ($record) => route('penjadwalan-service.print', $record))
                ->openUrlInNewTab(),
            Actions\Action::make('print_simple')
                ->label('Invoice Simple')
                ->icon('heroicon-m-document-text')
                ->color('gray')
                ->url(fn ($record) => route('penjadwalan-service.invoice.simple', $record))
                ->openUrlInNewTab(),
            Actions\Action::make('print_crosscheck')
                ->label('Cetak Checklist')
                ->icon('heroicon-m-clipboard-document-check')
                ->color('info')
                ->visible(fn ($record) => $record->has_crosscheck)
                ->url(fn ($record) => route('penjadwalan-service.print-crosscheck', $record))
                ->openUrlInNewTab(),
        ];
    }
}
