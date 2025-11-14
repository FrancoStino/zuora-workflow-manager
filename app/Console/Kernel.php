<?php

namespace App\Console;

use App\Jobs\SyncCustomerWorkflows;
use App\Models\Customer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sincronizza i workflow di tutti i customer ogni ora
        $schedule->call(function () {
            Customer::all()->each(fn (Customer $customer) => SyncCustomerWorkflows::dispatch($customer));
        })->hourly()->name('sync-customer-workflows');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
