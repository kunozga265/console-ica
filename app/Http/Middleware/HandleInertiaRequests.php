<?php

namespace App\Http\Middleware;

use App\Support\UIShellData;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'ui';


    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'flash'=>function() use ($request){
                return[
                    'info'      =>$request->session()->get('info'),
                    'warning'      =>$request->session()->get('warning'),
                    'success'   =>$request->session()->get('success'),
                    'error'     =>$request->session()->get('error'),
                ];
            },
             'publicPath'=> function() use ($request){
                return env("APP_URL");
            },
            // Kept as top-level props (not nested under "auth") since
            // Jetstream's own ShareInertiaData middleware already shares an
            // "auth.user" prop via a plain array_merge — nesting under the
            // same key here would non-deterministically clobber one or the
            // other depending on middleware order.
            'roles' => function () use ($request) {
                return $request->user()?->roles->pluck('name') ?? [];
            },
            'isMember' => function () use ($request) {
                return $request->user()?->member_id !== null;
            },
        ], ! $request->is('admin', 'admin/*') ? [
            // Layout data for the site's pages (top-bar live services, rail cell meeting, …).
            'liveServices' => fn () => UIShellData::liveServices($request),
            'nextCellMeeting' => fn () => UIShellData::nextCellMeeting($request),
            'sermonSaves' => fn () => UIShellData::sermonSaves($request),
            'notifications' => fn () => UIShellData::notifications($request),
            'isAdmin' => fn () => (bool) $request->user()?->hasAnyRole(['admin', 'super']),
            // Lets the sign-in pages explain why they're shown after scanning a check-in QR code.
            'authIntent' => fn () => str_contains((string) $request->session()->get('url.intended'), '/check-in/') ? 'check-in' : null,
        ] : [
            // Sidebar counts for the admin area.
            'adminCounts' => fn () => \App\Support\AdminCounts::get(),
        ]);
    }
}
