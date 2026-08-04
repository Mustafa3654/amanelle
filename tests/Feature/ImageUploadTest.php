<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Builds a genuine raster file rather than a fake one: UploadedFile::fake()
     * ->image() produces something GD can open, which is exactly what the
     * converter needs to be exercised for real.
     */
    private function upload(string $name, int $width = 2400, int $height = 1800): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    public function test_a_png_is_stored_as_webp(): void
    {
        Storage::fake('public');

        $path = ImageService::make()->store($this->upload('shot.png'), 'products');

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        // Verify the bytes really are WebP, not just the extension.
        $bytes = Storage::disk('public')->get($path);
        $this->assertSame('RIFF', substr($bytes, 0, 4));
        $this->assertSame('WEBP', substr($bytes, 8, 4));
    }

    public function test_a_jpeg_is_stored_as_webp(): void
    {
        Storage::fake('public');

        foreach (['bottle.jpg', 'bottle.jpeg'] as $name) {
            $path = ImageService::make()->store($this->upload($name), 'products');

            $this->assertStringEndsWith('.webp', $path);
            $this->assertSame('WEBP', substr(Storage::disk('public')->get($path), 8, 4));
        }
    }

    public function test_oversized_images_are_scaled_down(): void
    {
        Storage::fake('public');

        $path = ImageService::make()->store($this->upload('huge.jpg', 4000, 3000), 'products');

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

        $this->assertSame(ImageService::MAX_EDGE, $width);
        $this->assertLessThanOrEqual(ImageService::MAX_EDGE, $height);
    }

    public function test_small_images_are_not_upscaled(): void
    {
        Storage::fake('public');

        $path = ImageService::make()->store($this->upload('small.png', 400, 300), 'products');

        [$width] = getimagesizefromstring(Storage::disk('public')->get($path));

        // Upscaling only adds bytes and softness.
        $this->assertSame(400, $width);
    }

    public function test_conversion_actually_saves_bytes(): void
    {
        Storage::fake('public');

        $original = $this->upload('big.png', 2000, 2000);
        $originalSize = filesize($original->getRealPath());

        $path = ImageService::make()->store($original, 'products');

        $this->assertLessThan($originalSize, Storage::disk('public')->size($path));
    }

    public function test_an_unreadable_file_is_still_stored_rather_than_lost(): void
    {
        Storage::fake('public');

        // Not a real image; conversion cannot succeed.
        $broken = UploadedFile::fake()->createWithContent('notes.png', 'this is not an image');

        $path = ImageService::make()->store($broken, 'products');

        // A failed conversion must not mean a lost upload and a stack trace.
        Storage::disk('public')->assertExists($path);
    }
}
