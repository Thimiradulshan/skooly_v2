<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:schedule-delivery')->hourly()->withoutOverlapping(30)->onOneServer();

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
