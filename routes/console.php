<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:reset-weekly-leaderboard')->weeklyOn(1, '00:00');

// Prune activity_log rows older than the configured retention (clean_after_days).
Schedule::command('activitylog:clean')->daily();
