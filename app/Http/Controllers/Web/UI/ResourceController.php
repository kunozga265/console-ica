<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** The app's downloadable documents ("downloads" table), viewable in-page. */
class ResourceController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('UI/Resources', [
            'resources' => Download::orderByDesc('date')->orderByDesc('id')->get()
                ->map(fn (Download $d) => [
                    'id'          => intval($d->id),
                    'title'       => $d->title,
                    'type'        => strtolower((string) $d->type), // pdf, ppt, …
                    'description' => $d->description,
                    'date'        => $d->date ? intval($d->date) * 1000 : null,
                    'url'         => $this->absolute($d->path),
                    'previewUrl'  => $this->previewUrl($d->path),
                    'downloadUrl' => $this->downloadUrl($d->path),
                ])->values(),
        ]);
    }

    private function absolute(string $path): string
    {
        return preg_match('#^https?://#', $path) ? $path : asset(ltrim($path, '/'));
    }

    /** An embeddable URL: Google Drive/Docs "preview", or the file itself. */
    private function previewUrl(string $path): ?string
    {
        if (preg_match('#drive\.google\.com/file/d/([\w-]+)#', $path, $m)) {
            return "https://drive.google.com/file/d/{$m[1]}/preview";
        }
        if (preg_match('#docs\.google\.com/(document|presentation|spreadsheets)/d/([\w-]+)#', $path, $m)) {
            return "https://docs.google.com/{$m[1]}/d/{$m[2]}/preview";
        }

        return preg_match('/\.pdf($|\?)/i', $path) ? $this->absolute($path) : null;
    }

    private function downloadUrl(string $path): string
    {
        if (preg_match('#drive\.google\.com/file/d/([\w-]+)#', $path, $m)) {
            return "https://drive.google.com/uc?export=download&id={$m[1]}";
        }
        if (preg_match('#docs\.google\.com/(document|presentation|spreadsheets)/d/([\w-]+)#', $path, $m)) {
            $format = ['document' => 'pdf', 'presentation' => 'pptx', 'spreadsheets' => 'xlsx'][$m[1]];

            return "https://docs.google.com/{$m[1]}/d/{$m[2]}/export/{$format}";
        }

        return $this->absolute($path);
    }
}
