<?php

namespace App\Http\Controllers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class Controller
{
    /**
     * Save an uploaded file to public/uploads/{folder} and return its relative path
     * (e.g. "uploads/parks/bwindi-3fa9c1.jpg"). Works on shared hosting without storage:link.
     */
    protected function storeUpload(UploadedFile $file, string $folder, ?string $old = null): string
    {
        $dir = config('pearl360.upload_dir').'/'.trim($folder, '/');
        File::ensureDirectoryExists(public_path($dir));

        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::lower(Str::random(6))
            .'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $file->move(public_path($dir), $name);

        $this->deleteUpload($old);

        return $dir.'/'.$name;
    }

    protected function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, config('pearl360.upload_dir').'/') && ! str_contains($path, '..')) {
            File::delete(public_path($path));
        }
    }
}
