<?php

namespace App\Support;

use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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

            $image = Image::load($file->getRealPath());

            // Resize only if larger than Full HD (1920x1080)
            $width = $image->getWidth();
            $height = $image->getHeight();
            $maxWidth = 1920;
            $maxHeight = 1080;

            if ($width > $maxWidth || $height > $maxHeight) {
                // Resize to fit within max dimensions while maintaining aspect ratio
                // fit(Fit::Max, w, h) works well for this
                $image->fit(\Spatie\Image\Enums\Fit::Max, $maxWidth, $maxHeight);
            }

            $image->format('webp')
                ->quality($quality)
                ->save($diskPath);

            return $path;
        } catch (Throwable $exception) {
            Log::warning('WebpUpload::store conversion failed; falling back to original upload.', [
                'error' => $exception->getMessage(),
                'file_name' => $component->getUploadedFileNameForStorage($file),
                'directory' => $component->getDirectory(),
                'disk' => $component->getDiskName(),
            ]);

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
            Log::warning('WebpUpload::storeBase64 conversion failed; falling back to jpg.', [
                'error' => $exception->getMessage(),
                'disk' => $disk,
                'directory' => $directory,
                'visibility' => $visibility,
            ]);

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
