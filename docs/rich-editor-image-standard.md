# Standard RichEditor Image Upload

This document outlines the standard way to configure Filament's `RichEditor` component to handle image uploads efficiently.

## Goal
Automatically optimize uploaded images by:
1.  **Resizing** to a maximum of **1080p** (maintaining aspect ratio).
2.  **Converting** to **WebP** format.
3.  **Compressing** to **80% quality**.

## Usage

Use the `App\Services\ImageUploadService::processRichEditorUpload` method within the `saveUploadedFileAttachmentsUsing` closure of your `RichEditor` component.
Ensure you also set the disk to `public` (or match the service's disk) using `fileAttachmentsDisk`.

### Example Code

> [!TIP]
> This code is tested and verified to work. Uses `saveUploadedFileAttachmentsUsing` to handle uploads correctly.

```php
use Filament\Forms\Components\RichEditor;
use App\Services\ImageUploadService;

RichEditor::make('description')
    ->label('Description')
    ->fileAttachmentsDisk('public') // Required to match the service
    ->saveUploadedFileAttachmentsUsing(fn ($file) => ImageUploadService::processRichEditorUpload($file, 'folder-name')),
```

### Parameters
-   `$file`: The uploaded file instance (passed automatically by Filament).
-   `$folder`: (Optional) The folder path within the `public` disk where images will be stored. Default is `'task-attachments'`.

## Requirements
-   PHP `gd` extension must be enabled.
-   The `public` disk must be configured in `config/filesystems.php`.
