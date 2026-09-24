<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class ImageService
{
    /**
     * Default compression quality (0-100).
     */
    protected int $quality = 75;

    /**
     * Thumbnail dimensions.
     */
    protected int $thumbnailWidth = 400;
    protected int $thumbnailHeight = 300;

    /**
     * Image manager.
     */
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Resolve the web-served public root path safely.
     *
     * Menggunakan public_path() (folder `public/`, doc root nginx) agar file yang diupload
     * benar-benar dapat diakses lewat URL — konsisten dengan delete() yang juga pakai public_path().
     */
    protected function publicRoot(string $path = ''): string
    {
        return rtrim(
            public_path(ltrim($path, '/')),
            '/'
        );
    }

    /**
     * Set compression quality.
     */
    public function setQuality(int $quality): self
    {
        $this->quality = max(0, min(100, $quality));
        return $this;
    }

    /**
     * Set thumbnail size.
     */
    public function setThumbnailSize(int $width, int $height): self
    {
        $this->thumbnailWidth  = $width;
        $this->thumbnailHeight = $height;
        return $this;
    }

    /**
     * Compress image + create thumbnail.
     *
     * @return array{original: string, thumbnail: string}
     */
    public function compressAndSave(
        UploadedFile $file,
        string $directory,
        ?string $filename = null
    ): array {
        $filename = $filename ?? date('Y-m-d_His') . '_' . uniqid();

        $fullDir = $this->publicRoot($directory);
        if (!File::isDirectory($fullDir)) {
            File::makeDirectory($fullDir, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $extension = 'jpg';
        }

        $image = $this->manager->read($file->getPathname());

        // Original
        $originalName = $filename . '.' . $extension;
        $originalPath = $directory . '/' . $originalName;

        $image->toJpeg($this->quality)
              ->save($this->publicRoot($originalPath));

        // Thumbnail
        $thumbName = $filename . '_thumb.' . $extension;
        $thumbPath = $directory . '/' . $thumbName;

        $image->cover($this->thumbnailWidth, $this->thumbnailHeight)
              ->toJpeg($this->quality)
              ->save($this->publicRoot($thumbPath));

        return [
            'original'  => $originalPath,
            'thumbnail' => $thumbPath,
        ];
    }

    /**
     * Compress image only (no thumbnail).
     */
    public function compress(
        UploadedFile $file,
        string $directory,
        ?string $filename = null
    ): string {
        $filename = $filename ?? date('Y-m-d_His') . '_' . uniqid();

        $fullDir = $this->publicRoot($directory);
        if (!File::isDirectory($fullDir)) {
            File::makeDirectory($fullDir, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $extension = 'jpg';
        }

        $image = $this->manager->read($file->getPathname());

        $outputName = $filename . '.' . $extension;
        $outputPath = $directory . '/' . $outputName;

        $image->toJpeg($this->quality)
              ->save($this->publicRoot($outputPath));

        return $outputPath;
    }

    /**
     * Delete image and thumbnail.
     */
    public function delete(string $path): void
    {
        if (!$path) {
            return;
        }

        $original = $this->publicRoot($path);
        if (File::exists($original)) {
            File::delete($original);
        }

        $thumbnailPath = $this->getThumbnailPath($path);
        $thumbnail = $this->publicRoot($thumbnailPath);

        if (File::exists($thumbnail)) {
            File::delete($thumbnail);
        }
    }

    /**
     * Get thumbnail path from original path.
     */
    public function getThumbnailPath(string $originalPath): string
    {
        $info = pathinfo($originalPath);

        return $info['dirname']
            . '/'
            . $info['filename']
            . '_thumb.'
            . ($info['extension'] ?? 'jpg');
    }

    /**
     * Check if thumbnail exists.
     */
    public function thumbnailExists(string $originalPath): bool
    {
        return File::exists(
            $this->publicRoot($this->getThumbnailPath($originalPath))
        );
    }
}
