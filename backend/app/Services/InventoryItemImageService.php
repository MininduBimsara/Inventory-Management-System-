<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InventoryItemImageService
{
    public function store(UploadedFile $image): string
    {
        return $image->store('inventory-items', 'public');
    }

    public function replace(?string $currentPath, UploadedFile $image): string
    {
        $newPath = $this->store($image);

        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        return $newPath;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
