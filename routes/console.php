<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Birthday greetings — every morning at 7am church time. Needs the scheduler running:
//   * * * * * cd /path/to/console-ica && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('birthdays:send')
    ->dailyAt('07:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

// Push new sermons (when they go live), today's prayer points (from 06:00) and new events.
Schedule::command('notifications:dispatch')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Morning reminder for people attending today's events.
Schedule::command('events:remind')
    ->dailyAt('07:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

// Daily nudge to the finance team until every proof of payment is receipted.
Schedule::command('finance:remind')
    ->dailyAt('08:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
