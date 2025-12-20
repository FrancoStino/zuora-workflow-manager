<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync all customer workflows
// Available options (MINIMUM: 1 minute - Laravel does not support shorter intervals):
// ->everyMinute()           - Every minute (MINIMUM POSSIBLE - for testing)
// ->everyFiveMinutes()      - Every 5 minutes
// ->everyTenMinutes()       - Every 10 minutes
// ->everyThirtyMinutes()    - Every 30 minutes
// ->hourly()                - Every hour (RECOMMENDED for production)
// ->daily()                 - Once a day (at 00:00)
// ->dailyAt('13:00')        - Once a day (at the specified time)
// ->twiceDaily(1, 13)       - Twice a day (01:00 and 13:00)

// Use the existing command instead of a closure
// Advantages: testable, executable manually, better logging
Schedule::command('app:sync-workflows --all')
    ->everySecond()
    ->name('sync-customer-workflows');

// Process the queue automatically every minute (for local development)
Schedule::command('queue:work')
    ->name('process-queue');
