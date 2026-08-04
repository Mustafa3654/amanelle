<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hourly is frequent enough for a 48-hour window and cheap — the query is
// indexed on reservation_expires_at and matches nothing most of the time.
Schedule::command('stock:release-expired')->hourly();
