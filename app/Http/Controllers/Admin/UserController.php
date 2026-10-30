<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Scopes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Who can sign in, and what each person may manage (scopes). Super users only. */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $access = $request->query('access'); // super | scoped

        $page = User::with(['roles', 'member:id,avatar,code'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])->orWhere('email', 'like', "%{$search}%")))
            ->when($access === 'super', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'super')))
            ->when($access === 'scoped', fn ($q) => $q->whereIn('id', DB::table('user_scopes')->select('user_id')))
            ->orderBy('first_name')->orderBy('last_name')
            ->paginate(20)->withQueryString();

        $scopes = DB::table('user_scopes')->whereIn('user_id', collect($page->items())->pluck('id'))->get()->groupBy('user_id');

        return Inertia::render('Admin/Users', [
            'users' => collect($page->items())->map(fn (User $u) => [
                'id'     => $u->id,
                'name'   => $u->fullName(),
                'email'  => $u->email,
                'avatar' => $u->member?->avatar,
                'member' => $u->member?->code,
                'super'  => $u->isSuper(),
                'scopes' => $u->isSuper() ? Scopes::keys() : array_values(array_intersect(Scopes::keys(), ($scopes[$u->id] ?? collect())->pluck('scope')->all())),
                'joined' => $u->created_at?->getTimestamp() * 1000,
            ]),
            'scopeOptions' => Scopes::options(),
            'paging'  => ['page' => $page->currentPage(), 'lastPage' => $page->lastPage(), 'total' => $page->total()],
            'filters' => ['search' => $search, 'access' => $access],
            'counts'  => [
                'users'  => User::count(),
                'scoped' => DB::table('user_scopes')->distinct('user_id')->count('user_id'),
                'supers' => User::whereHas('roles', fn ($r) => $r->where('name', 'super'))->count(),
            ],
        ]);
    }

    /** Assign what a user may manage. Super users (who have everything) and yourself can't be changed. */
    public function updateScopes(Request $request, User $user)
    {
        if ($user->isSuper()) {
            return back()->withErrors(['scopes' => 'Super users have every scope.']);
        }
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['scopes' => 'You can’t change your own access.']);
        }

        $v = $request->validate([
            'scopes'   => ['present', 'array'],
            'scopes.*' => ['string', Rule::in(Scopes::keys())],
        ]);
        $user->syncScopes($v['scopes']);

        return back()->with('success', $v['scopes'] ? "Updated {$user->fullName()}’s access" : "{$user->fullName()} no longer manages anything");
    }
}
