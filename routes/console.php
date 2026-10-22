<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Run `php artisan schedule:work` locally, or a cron entry calling
| `php artisan schedule:run` every minute in production.
|
*/

Schedule::command('sessions:send-reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:clean')->dailyAt('01:00')->onOneServer();
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping()->onOneServer();
Schedule::command('backups:mirror-storage', ['--prune'])->dailyAt('02:30')->withoutOverlapping()->onOneServer();
Schedule::command('backup:monitor')->dailyAt('07:00')->onOneServer();

Schedule::command('queue:prune-failed', ['--hours' => 24 * 14])->daily();
