<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Permanently deletes a user's account and the data tied to it (app-store
 * account deletion). Personal records are deleted; shared records keep
 * working without the link to the person:
 *  - deleted: highlights, bookmarks, notes, saved/favourite sermons, prayer and
 *    event responses, cell join requests, notifications, app usage, roles,
 *    API tokens, sessions, and the user row itself (hard delete).
 *  - anonymised: sermon view counts keep their totals but lose the user id;
 *    cells/ministries they owned and join requests they handled lose the link.
 *  - kept: the church's member record (directory and attendance history,
 *    maintained by the church), which is only unlinked from the account.
 */
class AccountDeletion
{
    public static function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $id = $user->id;

            foreach (['highlights', 'bookmarks', 'notes', 'sermon_saves', 'prayer_user', 'event_responses',
                      'cell_join_requests', 'ui_notifications', 'usage_user', 'user_role', 'sessions'] as $table) {
                DB::table($table)->where('user_id', $id)->delete();
            }
            DB::table('personal_access_tokens')->where('tokenable_type', User::class)->where('tokenable_id', $id)->delete();

            DB::table('views')->where('user_id', $id)->update(['user_id' => null]);
            DB::table('cells')->where('user_id', $id)->update(['user_id' => null]);
            DB::table('ministries')->where('user_id', $id)->update(['user_id' => null]);
            DB::table('cell_join_requests')->where('handled_by', $id)->update(['handled_by' => null]);

            $user->forceDelete();
        });
    }
}
