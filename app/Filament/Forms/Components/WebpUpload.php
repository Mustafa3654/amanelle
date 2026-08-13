<?php

namespace App\Filament\Forms\Components;

use App\Services\ImageService;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A FileUpload that always lands as WebP.
 *
 * Wrapped as a component rather than repeated per field so no image field can
 * be added later that quietly skips the conversion.
 */
class WebpUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->image()
            // The accepted list is what a person can upload; what gets stored
            // is always WebP. WebP itself is accepted so re-uploading an
            // already-converted file is not rejected.
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(8 * 1024)
            ->imageEditor()
            ->disk('public')
            ->visibility('public')
            ->helperText(__('JPG, PNG or WebP. Converted to WebP automatically — smaller files, faster pages.'))
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, $component) {
                $directory = $component->getDirectory() ?? 'uploads';

                return ImageService::make()->store(
                    // Livewire's temporary file is a real UploadedFile
                    // underneath, which is what the converter wants.
                    new UploadedFile($file->getRealPath(), $file->getClientOriginalName()),
                    is_string($directory) ? $directory : 'uploads',
                    $component->getDiskName(),
                );
            });
    }
}
