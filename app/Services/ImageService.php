<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Converts every uploaded image to WebP on the way in.
 *
 * WebP is typically 25-35% smaller than JPEG at the same quality and far
 * smaller than PNG for photography, which matters here: the audience is on
 * mobile, in Lebanon, often on patchy connections, and a product grid is
 * mostly image bytes.
 *
 * The original is never kept. Storing both doubles the disk for a file that
 * would never be served.
 */
class ImageService
{
    /** Longest edge. Beyond this is invisible on a phone and costs bytes. */
    public const MAX_EDGE = 1600;

    public const QUALITY = 82;

    public function __construct(private ImageManager $manager) {}

    public static function make(): self
    {
        // GD rather than Imagick: it is what this server has, and it handles
        // WebP fine.
        return new self(new ImageManager(new Driver));
    }

    /**
     * Store an upload as WebP and return its path on the disk.
     *
     * Falls back to storing the file untouched if conversion fails — an
     * unconvertible image is better than a lost upload and a stack trace in
     * the admin's face.
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $name = Str::random(40).'.webp';
        $path = trim($directory, '/').'/'.$name;

        try {
            $image = $this->manager->read($file->getRealPath());

            // Phone cameras record orientation in EXIF rather than rotating
            // the pixels; without this, portrait shots arrive sideways.
            $image->orient();

            // scaleDown, not scale: never upscale a small image, which only
            // adds bytes and softness.
            $image->scaleDown(width: self::MAX_EDGE, height: self::MAX_EDGE);

            // Re-encoding drops EXIF with it, so location data from a phone
            // does not get published along with the product shot.
            $encoded = $image->toWebp(quality: self::QUALITY);

            Storage::disk($disk)->put($path, (string) $encoded);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('WebP conversion failed; storing the original', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return $file->store($directory, $disk);
        }
    }
}
