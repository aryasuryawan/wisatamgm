<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder jadwal H-3 dan H-1 ke peserta (PRD 4.5). Queue worker harus jalan.
Schedule::command('schedule:remind')->dailyAt('08:00');
