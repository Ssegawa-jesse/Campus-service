<?php

use Illuminate\Support\Facades\Schedule;

// Run with `php artisan schedule:work` locally, or a cron entry calling `php artisan schedule:run` every minute on the server.
Schedule::command('library:daily')->dailyAt('08:00');
