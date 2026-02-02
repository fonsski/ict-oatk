<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class ImageService
{
    
     * Default image quality

    protected const DEFAULT_QUALITY = 80;

    
     * Default thumbnail size

    protected const DEFAULT_THUMBNAIL_SIZE = 200;

    
     * Image formats that support transparency

    protected const TRANSPARENT_FORMATS = ['png', 'webp'];

    
     * Maximum image size in kilobytes

    protected const MAX_IMAGE_SIZE = 5120; 

    
     * Allowed image mime types

    protected const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    
     * Disk to store images

    protected $disk;

    
     * Create a new ImageService instance.

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'public');
    }

    
     * Set the storage disk.
     *
     * @param string $disk
     * @return $this

    public function setDisk(string $disk)
    {
        $this->disk = $disk;
        return $this;
    }

    
     * Upload and optimize an image.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param string $path
     * @param int|null $quality
     * @param bool $generateThumbnail
     * @param int|null $thumbnailSize
     * @return array
     * @throws \Exception

    public function upload(
        UploadedFile $image,
        string $path,
        ?int $quality = null,
        bool $generateThumbnail = false,
        ?int $thumbnailSize = null
    ): array {
        try {
            
            $this->validateImage($image);

            
            $filename = $this->generateFilename($image);
            $fullPath = trim($path, '/') . '/' . $filename;

            
            $processedImage = $this->processImage($image, $quality);

            
            Storage::disk($this->disk)->put($fullPath, $processedImage->encode());

            $result = [
                'path' => $fullPath,
                'url' => Storage::disk($this->disk)->url($fullPath),
                'filename' => $filename,
                'mime_type' => $image->getMimeType(),
                'size' => Storage::disk($this->disk)->size($fullPath),
            ];

            
            if ($generateThumbnail) {
                $thumbnail = $this->generateThumbnail($image, $thumbnailSize ?? self::DEFAULT_THUMBNAIL_SIZE);
                $thumbnailPath = trim($path, '/') . '/thumbnails/' . $filename;

                Storage::disk($this->disk)->put($thumbnailPath, $thumbnail->encode());

                $result['thumbnail'] = [
                    'path' => $thumbnailPath,
                    'url' => Storage::disk($this->disk)->url($thumbnailPath),
                    'size' => Storage::disk($this->disk)->size($thumbnailPath),
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    
     * Delete an image and its thumbnail if exists.
     *
     * @param string $path
     * @return bool

    public function delete(string $path): bool
    {
        try {
            $thumbnailPath = $this->getThumbnailPath($path);

            
            if (Storage::disk($this->disk)->exists($thumbnailPath)) {
                Storage::disk($this->disk)->delete($thumbnailPath);
            }

            
            return Storage::disk($this->disk)->delete($path);
        } catch (\Exception $e) {
            Log::error('Image deletion failed', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return false;
        }
    }

    
     * Process and optimize an image.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param int|null $quality
     * @return \Intervention\Image\Image

    protected function processImage(UploadedFile $image, ?int $quality = null)
    {
        
        $img = Image::make($image);

        
        $quality = $quality ?? self::DEFAULT_QUALITY;

        
        $img->orientate();

        
        if ($image->getMimeType() === 'image/jpeg') {
            
            if ($img->exif('ColorSpace') == 'CMYK') {
                $img->getCore()->transformImageColorspace(\Imagick::COLORSPACE_RGB);
            }

            
            $img->getCore()->stripImage();
        }

        
        $maxDimension = 2000;
        if ($img->width() > $maxDimension || $img->height() > $maxDimension) {
            $img->resize($maxDimension, $maxDimension, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        return $img;
    }

    
     * Generate a thumbnail.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param int $size
     * @return \Intervention\Image\Image

    protected function generateThumbnail(UploadedFile $image, int $size)
    {
        $img = Image::make($image);

        
        $img->fit($size);

        return $img;
    }

    
     * Get the thumbnail path for an image.
     *
     * @param string $path
     * @return string

    protected function getThumbnailPath(string $path): string
    {
        $pathInfo = pathinfo($path);
        $directory = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['basename'] ?? '';

        return trim($directory, '/') . '/thumbnails/' . $filename;
    }

    
     * Validate an image.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return void
     * @throws \Exception

    protected function validateImage(UploadedFile $image): void
    {
        
        if (!$image->isValid()) {
            throw new \Exception('Invalid image file');
        }

        
        if (!in_array($image->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('Unsupported image format. Allowed formats: JPEG, PNG, WebP, GIF');
        }

        
        if ($image->getSize() > self::MAX_IMAGE_SIZE * 1024) {
            throw new \Exception('Image size exceeds the maximum allowed (' . self::MAX_IMAGE_SIZE . 'KB)');
        }
    }

    
     * Generate a unique filename.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return string

    protected function generateFilename(UploadedFile $image): string
    {
        $extension = $image->getClientOriginalExtension();
        $basename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $basename = Str::slug($basename);

        
        $uniqueId = Str::random(10);

        return $basename . '_' . $uniqueId . '.' . $extension;
    }

    
     * Resize an image.
     *
     * @param string $path
     * @param int $width
     * @param int $height
     * @param bool $keepAspectRatio
     * @return string

    public function resize(string $path, int $width, int $height, bool $keepAspectRatio = true): string
    {
        try {
            $img = Image::make(Storage::disk($this->disk)->path($path));

            if ($keepAspectRatio) {
                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            } else {
                $img->resize($width, $height);
            }

            
            $pathInfo = pathinfo($path);
            $directory = $pathInfo['dirname'] ?? '';
            $filename = $pathInfo['filename'] ?? '';
            $extension = $pathInfo['extension'] ?? '';

            $newPath = trim($directory, '/') . '/' . $filename . "_{$width}x{$height}." . $extension;

            Storage::disk($this->disk)->put($newPath, $img->encode());

            return $newPath;
        } catch (\Exception $e) {
            Log::error('Image resize failed', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return $path; 
        }
    }

    
     * Add a watermark to an image.
     *
     * @param string $path
     * @param string $watermarkPath
     * @param string $position
     * @param int $opacity
     * @return string

    public function addWatermark(
        string $path,
        string $watermarkPath,
        string $position = 'bottom-right',
        int $opacity = 30
    ): string {
        try {
            $img = Image::make(Storage::disk($this->disk)->path($path));
            $watermark = Image::make(Storage::disk($this->disk)->path($watermarkPath));

            
            $watermark->opacity($opacity);

            
            switch ($position) {
                case 'top-left':
                    $x = 10;
                    $y = 10;
                    break;
                case 'top-right':
                    $x = $img->width() - $watermark->width() - 10;
                    $y = 10;
                    break;
                case 'bottom-left':
                    $x = 10;
                    $y = $img->height() - $watermark->height() - 10;
                    break;
                case 'center':
                    $x = ($img->width() - $watermark->width()) / 2;
                    $y = ($img->height() - $watermark->height()) / 2;
                    break;
                case 'bottom-right':
                default:
                    $x = $img->width() - $watermark->width() - 10;
                    $y = $img->height() - $watermark->height() - 10;
                    break;
            }

            
            $img->insert($watermark, $position, (int)$x, (int)$y);

            
            $pathInfo = pathinfo($path);
            $directory = $pathInfo['dirname'] ?? '';
            $filename = $pathInfo['filename'] ?? '';
            $extension = $pathInfo['extension'] ?? '';

            $newPath = trim($directory, '/') . '/' . $filename . '_watermarked.' . $extension;

            Storage::disk($this->disk)->put($newPath, $img->encode());

            return $newPath;
        } catch (\Exception $e) {
            Log::error('Adding watermark failed', [
                'error' => $e->getMessage(),
                'path' => $path,
                'watermark' => $watermarkPath,
            ]);

            return $path; 
        }
    }
}
