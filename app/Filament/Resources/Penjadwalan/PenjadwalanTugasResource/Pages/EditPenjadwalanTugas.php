<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPenjadwalanTugas extends EditRecord
{
    protected static string $resource = PenjadwalanTugasResource::class;

    /**
     * Check if current user can modify this task.
     * Only super_admin, assigned users (ditugaskan ke), and task creator (pemberi tugas) are allowed.
     */
    protected function canModifyTask($record): bool
    {
        $user = auth()->user();

        // Super admin can always modify
        if ($user->hasRole('super_admin')) {
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['durasi_pengerjaan']) && in_array($data['durasi_pengerjaan'], ['1', '2', '3'])) {
            $days = (int) $data['durasi_pengerjaan'];
            $data['tanggal_mulai'] = now()->toDateString();
            $data['deadline'] = now()->addDays($days - 1)->toDateString();
        }

        return $data;
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Check authorization
        if (!$this->canModifyTask($this->record)) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki izin untuk mengedit tugas ini.')
                ->danger()
                ->send();

            $this->redirect(PenjadwalanTugasResource::getUrl('view', ['record' => $this->record]));
            return;
        }

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
