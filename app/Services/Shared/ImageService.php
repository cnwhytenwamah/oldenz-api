<?php

namespace App\Services\Shared;

use App\Services\BaseService;
use Intervention\Image\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// use Intervention\Image\Facades\Image;

class ImageService extends BaseService
{
    /**
     * Upload and process product image
     */
    public function uploadProductImage(UploadedFile $file, int $productId): array
    {
        $filename = $this->generateUniqueFilename($file);
        $path = "products/{$productId}";

        $originalPath = Storage::putFileAs(
            $path,
            $file,
            $filename
        );

        $thumbnailPath = $this->generateThumbnail($file, $path, $filename);

        $mediumPath = $this->generateMediumImage($file, $path, $filename);

        return [
            'path' => $originalPath,
            'url' => Storage::url($originalPath),
            'thumbnail_url' => Storage::url($thumbnailPath),
            'medium_url' => Storage::url($mediumPath),
        ];
    }

    /**
     * Upload category image
     */
    public function uploadCategoryImage(UploadedFile $file, int $categoryId): array
    {
        $filename = $this->generateUniqueFilename($file);
        $path = "categories/{$categoryId}";

        $originalPath = Storage::putFileAs($path, $file, $filename);

        $thumbnailPath = $this->generateThumbnail($file, $path, $filename);

        return [
            'path' => $originalPath,
            'url' => Storage::url($originalPath),
            'thumbnail_url' => Storage::url($thumbnailPath),
        ];
    }

    /**
     * Generate thumbnail
     */
    private function generateThumbnail(UploadedFile $file, string $path, string $filename): string
    {
        $thumbnailName = 'thumb_' . $filename;
        
        $image = Image::make($file)->fit(200, 200)->encode('jpg', 80);

        Storage::put("{$path}/{$thumbnailName}", $image);

        return "{$path}/{$thumbnailName}";
    }

    /**
     * Generate medium size image
     */
    private function generateMediumImage(UploadedFile $file, string $path, string $filename): string
    {
        $mediumName = 'medium_' . $filename;
        
        $image = Image::make($file)
            ->resize(800, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode('jpg', 85);

        Storage::put("{$path}/{$mediumName}", $image);

        return "{$path}/{$mediumName}";
    }

    /**
     * Delete product images
     */
    public function deleteProductImages(int $productId): bool
    {
        return Storage::deleteDirectory("products/{$productId}");
    }

    /**
     * Delete category image
     */
    public function deleteCategoryImage(int $categoryId): bool
    {
        return Storage::deleteDirectory("categories/{$categoryId}");
    }

    /**
     * Delete single image
     */
    public function deleteImage(string $path): bool
    {
        return Storage::delete($path);
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        return time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Optimize image
     */
    public function optimizeImage(string $path, int $quality = 85): bool
    {
        try {
            $image = Image::make(Storage::get($path))->encode('jpg', $quality);

            Storage::put($path, $image);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Resize image
     */
    public function resizeImage(string $path, int $width, ?int $height = null): bool
    {
        try {
            $image = Image::make(Storage::get($path))
                ->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

            Storage::put($path, $image);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add watermark
     */
    public function addWatermark(string $path, string $watermarkPath): bool
    {
        try {
            $image = Image::make(Storage::get($path));
            $watermark = Image::make(Storage::get($watermarkPath));

            $image->insert($watermark, 'bottom-right', 10, 10);
            
            Storage::put($path, $image);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
