<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\API\V1_1\AppController as V1_1AppController;
use App\Http\Controllers\Controller;
use App\Http\Resources\DownloadCollection;
use App\Models\Download;
use Illuminate\Http\Request;

/** ICA Resources: ?search= on the title, ?recent=14 for the dashboard's "new resources". */
class DownloadController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $recent = intval($request->query('recent'));

        $downloads = Download::query()
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($recent > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays(min($recent, 90))))
            ->orderBy('date', 'desc')
            ->paginate((new V1_1AppController())->paginate);

        return response()->json(new DownloadCollection($downloads));
    }
}
