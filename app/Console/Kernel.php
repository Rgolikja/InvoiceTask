<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{

    protected function schedule(Schedule $schedule)
    {


        $schedule->command('invoices:fiscalize')->everyFiveMinutes()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/fiscalization.log'));

        $schedule->command('elif:declare-cashdesk')->dailyAt('08:00');
    }


    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }
}
