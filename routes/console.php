<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Handle queue processing on shared hosting.
 * This will run every minute via the cron job 'php artisan schedule:run'.
 */
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
