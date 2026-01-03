<?php

namespace App\Support;

use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Image;
use Throwable;

class WebpUpload
{
    public static function store(BaseFileUpload $component, TemporaryUploadedFile $file, int $quality = 80): ?string
    {
        try {
            if (! $file->exists()) {
                return null;
            }
        } catch (Throwable $exception) {
            return null;
        }

        try {
            $directory = $component->getDirectory() ?? '';
            $baseName = pathinfo($component->getUploadedFileNameForStorage($file), PATHINFO_FILENAME);
            $fileName = $baseName . '.webp';
            $path = trim($directory . '/' . $fileName, '/');

            $disk = $component->getDisk();
            if ($directory !== '') {
                $disk->makeDirectory($directory);
            }

            $diskPath = $disk->path($path);

            Image::load($file->getRealPath())
                ->format('webp')
                ->quality($quality)
                ->save($diskPath);

            return $path;
        } catch (Throwable $exception) {
            $storeMethod = $component->getVisibility() === 'public' ? 'storePubliclyAs' : 'storeAs';

            return $file->{$storeMethod}(
                $component->getDirectory(),
                $component->getUploadedFileNameForStorage($file),
                $component->getDiskName(),
            );
        }
    }

    public static function storeBase64(
        string $base64Data,
        string $disk,
        ?string $directory,
        string $visibility = 'public',
        int $quality = 80
    ): ?string {
        $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $base64Data);
        $imageData = base64_decode($base64Data);

        if (! $imageData) {
            return null;
        }

        $inputPath = tempnam(sys_get_temp_dir(), 'img_');
        $outputPath = tempnam(sys_get_temp_dir(), 'webp_');

        if ($inputPath === false || $outputPath === false) {
            return null;
        }

        try {
            file_put_contents($inputPath, $imageData);

            $fileName = Str::uuid() . '.webp';
            $path = $directory ? trim($directory . '/' . $fileName, '/') : $fileName;

            Image::load($inputPath)
                ->format('webp')
                ->quality($quality)
                ->save($outputPath);

            Storage::disk($disk)->put($path, file_get_contents($outputPath), $visibility);

            return $path;
        } catch (Throwable $exception) {
            $fileName = Str::uuid() . '.jpg';
            $path = $directory ? trim($directory . '/' . $fileName, '/') : $fileName;

            Storage::disk($disk)->put($path, $imageData, $visibility);

            return $path;
        } finally {
            if (is_file($inputPath)) {
                @unlink($inputPath);
            }

            if (is_file($outputPath)) {
                @unlink($outputPath);
            }
        }
    }
}
