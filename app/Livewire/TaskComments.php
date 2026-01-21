<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskComments extends Component
{
    use WithFileUploads;

    public \App\Models\PenjadwalanTugas $record;
    public string $body = '';
    public $attachments = [];
    public $newAttachments = []; // Temporary property for new uploads

    public function mount(\App\Models\PenjadwalanTugas $record)
    {
        $this->record = $record;
    }

    /**
     * When new files are uploaded, merge them with existing attachments
     */
    public function updatedNewAttachments()
    {
        // Validate new uploads
        $this->validate([
            'newAttachments.*' => 'file|max:10240', // Max 10MB per file
        ]);

        // Merge with existing attachments (limit to 10 files total)
        foreach ($this->newAttachments as $file) {
            if (count($this->attachments) < 10) {
                $this->attachments[] = $file;
            }
        }

        // Clear the temporary property
        $this->newAttachments = [];
    }

    public function submit()
    {
        $this->validate([
            'body' => 'required|string|max:1000',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240', // Max 10MB per file
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

        // Process attachments
        $storedPaths = [];
        foreach ($this->attachments as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

            if (in_array($extension, $imageExtensions)) {
                // Process image with ImageUploadService (resize & convert to webp)
                $path = ImageUploadService::processRichEditorUpload($file, 'task-comment-attachments');
            } else {
                // Store other files directly
                $filename = Str::ulid() . '.' . $extension;
                $path = $file->storeAs('task-comment-attachments', $filename, 'public');
            }

            if ($path) {
                $storedPaths[] = $path;
            }
        }

        \App\Models\TaskComment::create([
            'penjadwalan_tugas_id' => $this->record->id,
            'user_id' => $user->id,
            'body' => $this->body,
            'attachments' => !empty($storedPaths) ? $storedPaths : null,
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
        $this->attachments = [];
        
        \Filament\Notifications\Notification::make()
            ->title('Komentar ditambahkan')
            ->success()
            ->send();
    }

    public function removeAttachment($index)
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function render()
    {
        return view('livewire.task-comments', [
            'comments' => $this->record->comments()->with('user')->latest()->get(),
        ]);
    }
}
