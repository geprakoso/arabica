# Multi-Upload Attachment Feature

This document describes the multi-file attachment feature in the Task Comments system.

## Overview

The comment system supports uploading multiple files that accumulate (not replace) when adding more attachments.

## Features

1. **Multi-file upload** - Select multiple files at once or add files incrementally
2. **Image processing** - Images are auto-resized to 1080p max and converted to WebP (80% quality)
3. **File type icons** - Documents display with extension-specific icons (PDF, DOC, XLS, etc.)
4. **iOS-style close button** - Gray circular X button on top-right corner for removing files
5. **64x64px thumbnails** - Consistent preview size for all attachments

## Implementation

### Livewire Component

Uses a two-property pattern for accumulating uploads:

```php
public $attachments = [];      // Accumulated files
public $newAttachments = [];   // Temporary for new uploads

public function updatedNewAttachments()
{
    // Merge new files with existing (max 10 files)
    foreach ($this->newAttachments as $file) {
        if (count($this->attachments) < 10) {
            $this->attachments[] = $file;
        }
    }
    $this->newAttachments = [];
}
```

### Close Button Style (iOS-style)

```blade
<button 
    wire:click="removeAttachment({{ $index }})"
    class="w-6 h-6 bg-gray-500 dark:bg-gray-600 text-white rounded-full flex items-center justify-center hover:bg-gray-600 dark:hover:bg-gray-500 shadow-md"
    style="position: absolute; top: -8px; right: -8px; z-index: 10;"
>
    <x-heroicon-m-x-mark class="w-4 h-4" />
</button>
```

### File Input

```blade
<input type="file" wire:model="newAttachments" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar" />
```

## Related Files

- `app/Livewire/TaskComments.php` - Livewire component
- `resources/views/livewire/task-comments.blade.php` - Blade template
- `app/Models/TaskComment.php` - Model with attachment helpers
- `app/Services/ImageUploadService.php` - Image processing service
