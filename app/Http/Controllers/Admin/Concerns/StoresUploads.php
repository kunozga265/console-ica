<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Saves an uploaded image under public/{directory}, as the old console did. */
trait StoresUploads
{
    protected function storeUpload(Request $request, string $field, string $directory): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $filename = Str::random(20) . '.' . $file->extension();
        $file->move(public_path($directory), $filename);

        return "{$directory}/{$filename}";
    }
}
