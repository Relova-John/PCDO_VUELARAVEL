<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('payments:check-due')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('notifications:process')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('notifications:cleanup')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('export:completed-loans')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('archive:coop-programs')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('check:delinquents')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
