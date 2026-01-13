<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewPenjadwalanTugas extends ViewRecord
{
    protected static string $resource = PenjadwalanTugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('proses')
                ->label('Proses')
                ->color('info')
                ->icon('heroicon-m-arrow-path')
                ->visible(fn ($record) => $record->status === \App\Enums\StatusTugas::Pending)
                ->action(fn ($record) => $record->update(['status' => \App\Enums\StatusTugas::Proses]))
                ->requiresConfirmation(),

            Actions\Action::make('selesai')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->visible(fn ($record) => in_array($record->status, [\App\Enums\StatusTugas::Pending, \App\Enums\StatusTugas::Proses]))
                ->action(fn ($record) => $record->update(['status' => \App\Enums\StatusTugas::Selesai]))
                ->requiresConfirmation(),

            Actions\Action::make('batal')
                ->label('Batal')
                ->color('danger')
                ->icon('heroicon-m-x-circle')
                ->visible(fn ($record) => ! in_array($record->status, [\App\Enums\StatusTugas::Selesai, \App\Enums\StatusTugas::Batal]))
                ->action(fn ($record) => $record->update(['status' => \App\Enums\StatusTugas::Batal]))
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalDescription('Apakah Anda yakin ingin membatalkan tugas ini?'),

            Actions\EditAction::make(),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        \App\Models\TaskView::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'penjadwalan_tugas_id' => $this->record->id,
            ],
            [
                'last_viewed_at' => now(),
            ]
        );
    }
}
