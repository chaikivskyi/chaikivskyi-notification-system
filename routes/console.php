<?php

use App\Console\Commands\SendScheduledNotifications;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SendScheduledNotifications::class)->everyMinute();
