<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageStorage
{
    public const RULES = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    public const MESSAGES = [
        'image' => 'El archivo debe ser una imagen.',
        'mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
        'max' => 'La imagen no puede superar los 2 MB.',
    ];

    public function replace(UploadedFile $file, string $directory, ?string $previous = null): string
    {
        $path = (string) $file->store($directory, 'public');

        $this->delete($previous);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/'.$path);
    }
}
