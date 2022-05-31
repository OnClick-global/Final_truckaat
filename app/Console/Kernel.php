<?php

namespace App\Console;

use App\Console\Commands\MonthelyReport;
use App\Models\MonthlyReport;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        MonthelyReport::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('Report:Monthly')
            ->timezone('Asia/Kuwait')->monthly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
