<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ministry;
use App\Models\Register;
use App\Models\Sermon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MinistryController extends Controller
{
    public function index()
    {
        $counts = fn ($model) => $model::selectRaw('ministry_id, COUNT(*) n')->groupBy('ministry_id')->pluck('n', 'ministry_id');
        $sermons = $counts(Sermon::class);
        $events = $counts(Event::class);
        $registers = $counts(Register::class);
        // ministries.user_id is the leader (Ministry::leader() guesses leader_id, so look up directly).
        $leaders = User::whereIn('id', Ministry::whereNotNull('user_id')->pluck('user_id'))->get()->mapWithKeys(fn ($u) => [$u->id => $u->fullName()]);

        return Inertia::render('Admin/Ministries', [
            'ministries' => Ministry::orderByRaw("slug = 'main-church' DESC")->orderBy('name')->get()
                ->map(fn (Ministry $m) => [
                    'id'        => $m->id,
                    'name'      => $m->name,
                    'slug'      => $m->slug,
                    'leaderId'  => $m->user_id,
                    'leader'    => $leaders[$m->user_id] ?? null,
                    'sermons'   => intval($sermons[$m->id] ?? 0),
                    'events'    => intval($events[$m->id] ?? 0),
                    'registers' => intval($registers[$m->id] ?? 0),
                ]),
            // Leaders are chosen from admins (they run the ministry's registers/events).
            'leaders' => User::whereHas('roles', fn ($r) => $r->whereIn('name', ['admin', 'super']))->orderBy('first_name')->get()
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->fullName()]),
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request);
        Ministry::create(['name' => $v['name'], 'slug' => $this->uniqueSlug($v['name']), 'user_id' => $v['leaderId'] ?? null]);

        return back()->with('success', 'Ministry added');
    }

    public function update(Request $request, Ministry $ministry)
    {
        $v = $this->validated($request, $ministry);
        $ministry->update(['name' => $v['name'], 'user_id' => $v['leaderId'] ?? null]);

        return back()->with('success', 'Ministry saved');
    }

    /** Only empty ministries can be deleted — sermons, events and registers point at them. */
    public function destroy(Ministry $ministry)
    {
        $used = Sermon::withTrashed()->where('ministry_id', $ministry->id)->exists()
            || Event::where('ministry_id', $ministry->id)->exists()
            || Register::where('ministry_id', $ministry->id)->exists();

        if ($used) {
            return back()->withErrors(['ministry' => "{$ministry->name} still has sermons, events or registers. Move them to another ministry first."]);
        }

        $ministry->delete();

        return back()->with('success', 'Ministry deleted');
    }

    private function validated(Request $request, ?Ministry $ministry = null): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:191', Rule::unique('ministries', 'name')->ignore($ministry?->id)],
            'leaderId' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $slug = $base = Str::slug($name);
        for ($i = 2; Ministry::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
