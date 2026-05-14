<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:capture-machine-counts')->dailyAt('02:00');
Schedule::command('billing:generate-invoices')->dailyAt('03:00');
Schedule::command('reports:poll-imap')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('reports:poll-microsoft-graph')->everyFiveMinutes()->withoutOverlapping();
