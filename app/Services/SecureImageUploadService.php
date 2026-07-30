<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SecureImageUploadService
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_WIDTH = 8000;
    private const MAX_HEIGHT = 8000;

    public function upload(UploadedFile $file, string $directory, ?string $filename = null): string
    {
        $this->validateFile($file);

        $safeFilename = $filename ?? $this->generateFilename($file);
        $destinationPath = $directory . '/' . $safeFilename;

        $cleanedImage = $this->sanitizeImage($file->getRealPath(), $file->getMimeType());

        Storage::disk('public')->put($destinationPath, $cleanedImage);

        $this->protectDirectory($directory);

        return $destinationPath;
    }

    public function uploadFromPath(string $sourcePath, string $directory, ?string $filename = null): string
    {
        $this->validateFilePath($sourcePath);

        $mime = $this->detectMime($sourcePath);
        $safeFilename = $filename ?? $this->generateFilenameFromMime($mime);
        $destinationPath = $directory . '/' . $safeFilename;

        $cleanedImage = $this->sanitizeImage($sourcePath, $mime);

        Storage::disk('public')->put($destinationPath, $cleanedImage);

        $this->protectDirectory($directory);

        return $destinationPath;
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('File upload gagal.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Ukuran file terlalu besar. Maksimal 10MB.');
        }

        $realMime = $this->detectMime($file->getRealPath());

        if (!in_array($realMime, self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException("Format tidak didukung: {$realMime}. Hanya JPG, PNG, WEBP.");
        }

        $this->scanForMaliciousContent($file->getRealPath());
    }

    private function validateFilePath(string $path): void
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException('File tidak ditemukan.');
        }

        $size = filesize($path);
        if ($size > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Ukuran file terlalu besar. Maksimal 10MB.');
        }

        $realMime = $this->detectMime($path);

        if (!in_array($realMime, self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException("Format tidak didukung: {$realMime}. Hanya JPG, PNG, WEBP.");
        }

        $this->scanForMaliciousContent($path);
    }

    private function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        $imageInfo = @getimagesize($path);
        if ($imageInfo && isset($imageInfo['mime'])) {
            $detectedMime = $imageInfo['mime'];
            if ($detectedMime !== $mime) {
                $mime = $detectedMime;
            }
        }

        return $mime;
    }

    private function scanForMaliciousContent(string $path): void
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new InvalidArgumentException('Gagal membaca file.');
        }

        $header = fread($handle, 8192);
        fclose($handle);

        $patterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<script\b/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec\s*\(/i',
            '/base64_decode\s*\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $header)) {
                throw new InvalidArgumentException('File terdeteksi mengandung konten berbahaya.');
            }
        }
    }

    private function sanitizeImage(string $sourcePath, string $mime): string
    {
        $img = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default      => null,
        };

        if (!$img) {
            throw new InvalidArgumentException('Gagal membaca gambar.');
        }

        $width = imagesx($img);
        $height = imagesy($img);

        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            imagedestroy($img);
            throw new InvalidArgumentException("Dimensi terlalu besar: {$width}x{$height}. Maksimal " . self::MAX_WIDTH . "x" . self::MAX_HEIGHT . ".");
        }

        if (imageistruecolor($img)) {
            $output = imagecreatetruecolor($width, $height);
            imagecopy($output, $img, 0, 0, 0, 0, $width, $height);
            imagedestroy($img);
            $img = $output;
        }

        ob_start();
        match ($mime) {
            'image/jpeg' => imagejpeg($img, null, 90),
            'image/png'  => imagepng($img, null, 9),
            'image/webp' => imagewebp($img, null, 90),
        };
        $cleanData = ob_get_clean();

        imagedestroy($img);

        return $cleanData;
    }

    private function generateFilename(UploadedFile $file): string
    {
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        return Str::random(32) . '.' . $extension;
    }

    private function generateFilenameFromMime(string $mime): string
    {
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        return Str::random(32) . '.' . $extension;
    }

    private function protectDirectory(string $directory): void
    {
        $fullPath = Storage::disk('public')->path($directory);

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $htaccess = $fullPath . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, <<<HTACCESS
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps|cgi|pl|asp|aspx|jsp|sh|bash)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(jpg|jpeg|png|gif|webp|svg|ico)$">
    Order Deny,Allow
    Allow from all
</FilesMatch>

Options -ExecCGI
AddHandler default-handler .jpg .jpeg .png .gif .webp
HTACCESS
            );
        }
    }

    public function deleteFile(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    public function getUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
