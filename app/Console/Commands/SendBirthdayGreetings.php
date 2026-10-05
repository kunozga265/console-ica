<?php

namespace App\Console\Commands;

use App\Mail\BirthdayGreeting;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a birthday greeting to every member whose birthday is today
 * (church timezone). Scheduled daily at 07:00 in routes/console.php.
 * Each member is greeted at most once a year, so re-runs are safe.
 */
class SendBirthdayGreetings extends Command
{
    protected $signature = 'birthdays:send
        {--date= : Act as if today were this date (Y-m-d), e.g. to catch up a missed day}
        {--dry-run : List who would be emailed without sending}';

    protected $description = "Email birthday greetings to members whose birthday is today";

    public function handle(): int
    {
        $tz = config('app.timezone');

        try {
            $today = $this->option('date') ? Carbon::createFromFormat('Y-m-d', $this->option('date'), $tz) : now($tz);
        } catch (\Throwable) {
            $this->error('--date must be Y-m-d');

            return self::INVALID;
        }

        // Feb 29 birthdays are celebrated on Feb 28 in non-leap years.
        $days = [$today->format('m-d')];
        if ($today->format('m-d') === '02-28' && ! $today->isLeapYear()) {
            $days[] = '02-29';
        }

        $alreadySent = DB::table('birthday_greetings')->where('year', $today->year)->pluck('member_id')->flip();

        $members = Member::query()
            ->whereNotNull('date_of_birth')
            ->with('user:id,member_id,email')
            ->get(['id', 'first_name', 'last_name', 'email', 'date_of_birth'])
            ->filter(fn (Member $m) => in_array(Carbon::createFromTimestamp($m->date_of_birth, $tz)->format('m-d'), $days, true))
            ->reject(fn (Member $m) => $alreadySent->has($m->id));

        $sent = $skipped = $failed = 0;

        foreach ($members as $member) {
            $email = collect([$member->email, $member->user?->email])
                ->first(fn ($e) => $e && filter_var($e, FILTER_VALIDATE_EMAIL));
            $name = trim("{$member->first_name} {$member->last_name}");

            if (! $email) {
                $skipped++;
                $this->line("  – {$name}: no email address");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  · {$name} <{$email}>");
                $sent++;
                continue;
            }

            try {
                Mail::to($email)->send(new BirthdayGreeting(trim($member->first_name) ?: 'friend'));
                DB::table('birthday_greetings')->insertOrIgnore([
                    'member_id' => $member->id,
                    'year' => $today->year,
                    'email' => $email,
                    'sent_at' => now(),
                ]);
                $sent++;
                $this->line("  ✓ {$name} <{$email}>");
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Birthday greeting failed', ['member_id' => $member->id, 'error' => $e->getMessage()]);
                $this->error("  ✗ {$name}: {$e->getMessage()}");
            }
        }

        $verb = $this->option('dry-run') ? 'would send' : 'sent';
        $this->info("Birthdays {$today->toDateString()}: {$verb} {$sent}, no email {$skipped}, failed {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
