<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire unfinished payments
Schedule::command('user-payments:expire --minutes=5')
	->everyFiveMinutes();

// Send notifications about new entries
Schedule::command('notifications:entries')
	->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Send subscription notices
Schedule::command('subscriptions:send-notices')
	->dailyAt('12:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Send emails
Schedule::command('emails:dispatch-due')
	->everyMinute()
	->withoutOverlapping()
	->runInBackground();
