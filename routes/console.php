<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/* Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote'); */

Schedule::command('enrollments:auto-cancel')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('enrollments:auto-generate')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
