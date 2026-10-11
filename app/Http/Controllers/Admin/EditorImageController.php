<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\StoresUploads;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Images inserted into sermon / prayer bodies from the WYSIWYG editor.
 * Stored in public/images/uploads like the old CMS's CKEditor uploads, and
 * returned as an absolute URL so the mobile app renders them too.
 */
class EditorImageController extends Controller
{
    use StoresUploads;

    public function __invoke(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        return response()->json(['url' => asset($this->storeUpload($request, 'image', 'images/uploads'))]);
    }
}
