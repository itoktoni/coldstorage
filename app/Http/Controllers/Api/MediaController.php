<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * List media files (images) with pagination.
     */
    public function index(Request $request)
    {
        $query = Media::images()->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                  ->orWhere('alt', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 24);
        $media = $query->paginate($perPage);

        // Transform to include computed attributes
        $media->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'filename' => $item->filename,
                'original_filename' => $item->original_filename,
                'mime_type' => $item->mime_type,
                'size' => $item->size,
                'url' => $item->url,
                'thumbnail' => $item->thumbnail,
                'alt' => $item->alt,
                'title' => $item->title,
                'width' => $item->width,
                'height' => $item->height,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json($media);
    }

    /**
     * Upload one or more images.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|file|mimes:jpeg,png,gif,webp,svg|max:10240',
        ]);

        $uploadedImages = [];
        $disk = 'public';

        foreach ($request->file('images', []) as $file) {
            $originalFilename = $file->getClientOriginalName();
            $filename = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media/' . date('Y/m'), $filename, $disk);

            // Get image dimensions
            $width = null;
            $height = null;
            $thumbnailPath = null;

            if (str_starts_with($file->getMimeType(), 'image/') && $file->getMimeType() !== 'image/svg+xml') {
                try {
                    $imageSize = getimagesize($file->getPathname());
                    if ($imageSize) {
                        $width = $imageSize[0];
                        $height = $imageSize[1];
                    }

                    // Create thumbnail directory
                    $thumbDir = 'media/' . date('Y/m') . '/thumbs';
                    if (!Storage::disk($disk)->exists($thumbDir)) {
                        Storage::disk($disk)->makeDirectory($thumbDir);
                    }

                    // Create thumbnail using GD
                    $thumbnailPath = $thumbDir . '/' . $filename;
                    $this->createThumbnail($file->getPathname(), Storage::disk($disk)->path($thumbnailPath), 300, 300);
                } catch (\Exception $e) {
                    // Thumbnail creation failed, continue without it
                }
            }

            $media = Media::create([
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'disk' => $disk,
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'width' => $width,
                'height' => $height,
                'user_id' => auth()->id(),
            ]);

            $uploadedImages[] = [
                'id' => $media->id,
                'filename' => $media->filename,
                'url' => $media->url,
                'thumbnail' => $media->thumbnail,
                'width' => $media->width,
                'height' => $media->height,
                'size' => $media->human_size,
            ];
        }

        return response()->json([
            'success' => true,
            'images' => $uploadedImages,
        ], 201);
    }

    /**
     * Delete a media item.
     */
    public function destroy(Media $media)
    {
        // Delete files from storage
        Storage::disk($media->disk)->delete($media->path);
        if ($media->thumbnail_path) {
            Storage::disk($media->disk)->delete($media->thumbnail_path);
        }

        $media->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Create a thumbnail from source image.
     */
    protected function createThumbnail(string $source, string $destination, int $maxWidth, int $maxHeight): bool
    {
        $imageSize = getimagesize($source);
        if (!$imageSize) {
            return false;
        }

        [$origWidth, $origHeight, $type] = $imageSize;

        // Calculate new dimensions
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        $newWidth = (int) ($origWidth * $ratio);
        $newHeight = (int) ($origHeight * $ratio);

        // Create image resource based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($source);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }

        // Resample
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Save thumbnail
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $destination, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $destination, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbnail, $destination);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumbnail, $destination, 85);
                break;
        }

        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);

        return true;
    }
}