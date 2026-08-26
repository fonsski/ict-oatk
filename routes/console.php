<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Напоминания о скором начале событий календаря. Служба ict-help-scheduler
// на сервере запускает планировщик раз в минуту.
Schedule::command('calendar:send-reminders')->everyMinute()->withoutOverlapping();
