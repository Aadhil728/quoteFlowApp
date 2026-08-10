<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => Cache::put('system:scheduler:last_seen_at', now()->toIso8601String(), now()->addDay()))
    ->name('quoteflow:heartbeat')
    ->everyFiveMinutes()
    ->withoutOverlapping();
