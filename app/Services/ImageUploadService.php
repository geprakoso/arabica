<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    /**
     * Handle image upload for RichEditor:
     * - Resize to max 1080p (aspect ratio maintained)
     * - Convert to WebP (Quality 80%)
     * - Store in public disk
     *
     * @param mixed $file The uploaded file instance
     * @param string $folder The destination folder in public disk
     * @return string|null The stored file path relative to the disk root
     */
    public static function processRichEditorUpload($file, string $folder = 'task-attachments'): ?string
    {
        // 1. Ambil path sementara file yang diupload
        $tempPath = $file->getRealPath();
        
        // 2. Baca konten gambar
        $imageContent = file_get_contents($tempPath);
        $image = @imagecreatefromstring($imageContent);
        
        if (!$image) {
            // Jika gagal load gambar (bukan gambar valid), fallback ke penyimpanan default
            return $file->store($folder, 'public');
        }
        
        // 3. Cek dimensi asli
        $width = imagesx($image);
        $height = imagesy($image);
        $maxSize = 1080;
        
        // 4. Resize jika melebihi batas 1080p (di salah satu sisi)
        if ($width > $maxSize || $height > $maxSize) {
            // Hitung rasio
            $ratio = $width / $height;
            
            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = $maxSize / $ratio;
            } else {
                $newWidth = $maxSize * $ratio;
                $newHeight = $maxSize;
            }
            
            // Buat canvas baru yang di-resize
            $scaled = imagescale($image, (int)$newWidth, (int)$newHeight);
            imagedestroy($image); // Hapus resource lama
            $image = $scaled;
        }
        
        // 5. Generate Filename baru (.webp)
        $filename = Str::ulid() . '.webp';
        
        // 6. Simpan ke Storage Publik sebagai WebP (Quality 80)
        ob_start();
        imagewebp($image, null, 80);
        $webpData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        // Simpan menggunakan Facade Storage
        $path = $folder . '/' . $filename;
        
        Storage::disk('public')->put($path, $webpData);
        
        // Kembalikan path relatif agar tersimpan di database/RichEditor
        return $path;
    }
}
