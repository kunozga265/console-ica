<?php

namespace App\Support;

use App\Models\Ministry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * What each user wants to be notified about. Everything is on by default:
 * a notification_preferences row only exists for a category someone changed,
 * and ministry_unsubscriptions lists the sermon ministries they muted.
 * The app subscribes to topics() (see App\Support\Push for the topic names).
 */
class NotificationSettings
{
    public const CATEGORIES = [
        'sermons'  => ['label' => 'Sermons', 'hint' => 'New sermons from the ministries you follow'],
        'prayer'   => ['label' => 'Prayer points', 'hint' => "Each day's prayer points"],
        'events'   => ['label' => 'Events', 'hint' => 'New events, and a reminder on the day of events you are attending'],
        'homecell' => ['label' => 'Homecell', 'hint' => 'Meetings and news from your homecell'],
        'general'  => ['label' => 'General', 'hint' => 'Church-wide announcements'],
    ];

    public static function categories(User $user): array
    {
        $saved = DB::table('notification_preferences')->where('user_id', $user->id)->pluck('enabled', 'category');

        return collect(self::CATEGORIES)->map(fn ($c, $key) => (bool) ($saved[$key] ?? true))->all();
    }

    /** @return array<int, array{id:int, name:string, subscribed:bool}> */
    public static function ministries(User $user): array
    {
        $muted = DB::table('ministry_unsubscriptions')->where('user_id', $user->id)->pluck('ministry_id')->map('intval')->flip();

        return Ministry::orderBy('id')->get(['id', 'name'])
            ->map(fn (Ministry $m) => ['id' => intval($m->id), 'name' => $m->name, 'subscribed' => ! $muted->has(intval($m->id))])
            ->all();
    }

    public static function enabled(User $user, string $category): bool
    {
        return self::categories($user)[$category] ?? true;
    }

    /** Everything the app needs: toggles, ministries, and the FCM topics to subscribe to. */
    public static function forUser(User $user): array
    {
        return [
            'categories' => self::categories($user),
            'labels'     => self::CATEGORIES,
            'ministries' => self::ministries($user),
            'topics'     => self::topics($user),
        ];
    }

    /** FCM topics for this user's choices. USER-{id} is always on (personal messages). */
    public static function topics(User $user): array
    {
        $on = self::categories($user);
        $topics = ["USER-{$user->id}"];

        if ($on['sermons']) {
            foreach (self::ministries($user) as $m) {
                if ($m['subscribed']) {
                    $topics[] = "SERMONS-MIN-{$m['id']}";
                }
            }
        }
        if ($on['prayer']) {
            $topics[] = 'PRAYER';
        }
        if ($on['events']) {
            $topics[] = 'EVENTS';
        }
        if ($on['general']) {
            $topics[] = 'GENERAL-V2';
        }
        if ($on['homecell'] && ($code = $user->member?->cell?->code)) {
            $topics[] = "CELL-{$code}";
        }

        return $topics;
    }

    /** @param array{categories?:array<string,bool>, ministries?:array<int,int>} $input ministries = the subscribed ids */
    public static function update(User $user, array $input): void
    {
        DB::transaction(function () use ($user, $input) {
            foreach ($input['categories'] ?? [] as $key => $enabled) {
                if (isset(self::CATEGORIES[$key])) {
                    DB::table('notification_preferences')->updateOrInsert(
                        ['user_id' => $user->id, 'category' => $key],
                        ['enabled' => (bool) $enabled, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }

            if (array_key_exists('ministries', $input)) {
                $subscribed = collect($input['ministries'])->map('intval');
                $muted = Ministry::whereNotIn('id', $subscribed)->pluck('id');
                DB::table('ministry_unsubscriptions')->where('user_id', $user->id)->whereNotIn('ministry_id', $muted)->delete();
                foreach ($muted as $id) {
                    DB::table('ministry_unsubscriptions')->insertOrIgnore(['user_id' => $user->id, 'ministry_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
    }
}
