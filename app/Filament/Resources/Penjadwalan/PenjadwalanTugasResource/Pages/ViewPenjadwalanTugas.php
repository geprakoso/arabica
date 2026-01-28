<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjadwalanTugas extends ViewRecord
{
    protected static string $resource = PenjadwalanTugasResource::class;

    /**
     * Check if current user can modify this task.
     * Only super_admin, assigned users (ditugaskan ke), and task creator (pemberi tugas) are allowed.
     */
    protected function canModifyTask($record): bool
    {
        $user = auth()->user();

        // Super admin or Godmode can always modify
        if ($user->hasRole(['super_admin', 'godmode'])) {
            return true;
        }

        $userId = $user->id;

        // Check if user is the task creator (pemberi tugas)
        if ($record->created_by === $userId) {
            return true;
        }

        // Check if user is assigned to the task (ditugaskan ke)
        if ($record->karyawan()->where('users.id', $userId)->exists()) {
            return true;
        }

        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('proses')
                ->label('Proses')
                ->color('info')
                ->icon('heroicon-m-arrow-path')
                ->visible(fn ($record) => $record->status === \App\Enums\StatusTugas::Pending && $this->canModifyTask($record))
                ->action(fn ($record) => $record->update(['status' => \App\Enums\StatusTugas::Proses]))
                ->requiresConfirmation(),

            Actions\Action::make('selesai')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->visible(fn ($record) => in_array($record->status, [\App\Enums\StatusTugas::Pending, \App\Enums\StatusTugas::Proses]) && $this->canModifyTask($record))
                ->action(fn ($record) => $record->update(['status' => \App\Enums\StatusTugas::Selesai]))
                ->requiresConfirmation(),

            Actions\Action::make('batal')
                ->label('Batal')
                ->color('danger')
                ->icon('heroicon-m-x-circle')
                ->visible(fn ($record) => ! in_array($record->status, [\App\Enums\StatusTugas::Selesai, \App\Enums\StatusTugas::Batal]) && $this->canModifyTask($record))
                ->action(fn ($record) => $record->update(['status' => \App\Enums\StatusTugas::Batal]))
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalDescription('Apakah Anda yakin ingin membatalkan tugas ini?'),

            Actions\EditAction::make()
                ->visible(fn ($record) => $this->canModifyTask($record)),
        ];
    }

    public function mount(int|string $record): void
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
