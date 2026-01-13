<?php

namespace App\Livewire;

use Livewire\Component;

class TaskComments extends Component
{
    public \App\Models\PenjadwalanTugas $record;
    public string $body = '';

    public function mount(\App\Models\PenjadwalanTugas $record)
    {
        $this->record = $record;
    }

    public function submit()
    {
        $this->validate([
            'body' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        
        // Authorization: Creator OR Assigned
        $isAssigned = $this->record->karyawan()->where('users.id', $user->id)->exists();
        $isCreator = $this->record->created_by == $user->id;

        if (! $isAssigned && ! $isCreator) {
             \Filament\Notifications\Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki izin untuk mengomentari tugas ini.')
                ->danger()
                ->send();
            return;
        }

        \App\Models\TaskComment::create([
            'penjadwalan_tugas_id' => $this->record->id,
            'user_id' => $user->id,
            'body' => $this->body,
        ]);

        // Send Notification to Assignees and Creator
        $recipients = $this->record->karyawan->pluck('id')->toArray();
        if ($this->record->created_by) {
            $recipients[] = $this->record->created_by;
        }
        $recipients = array_unique($recipients);
        $recipients = array_diff($recipients, [$user->id]); // Exclude current user

        if (count($recipients) > 0) {
            $receivers = \App\Models\User::whereIn('id', $recipients)->get();
            
            \Filament\Notifications\Notification::make()
                ->title('Komentar baru di tugas: ' . \Illuminate\Support\Str::limit($this->record->judul, 20))
                ->body('**' . $user->name . '**: ' . \Illuminate\Support\Str::limit($this->body, 50))
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat')
                        ->url(\App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource::getUrl('view', ['record' => $this->record->id]))
                        ->button(),
                ])
                ->sendToDatabase($receivers);
        }

        $this->body = '';
        
        \Filament\Notifications\Notification::make()
            ->title('Komentar ditambahkan')
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.task-comments', [
            'comments' => $this->record->comments()->with('user')->latest()->get(),
        ]);
    }
}
