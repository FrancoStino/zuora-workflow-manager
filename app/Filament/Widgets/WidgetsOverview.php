<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Resources\Workflows\WorkflowResource;
use App\Models\Customer;
use App\Models\Task;
use App\Models\Workflow;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Moox\Jobs\Models\JobManager;
use Moox\Jobs\Resources\JobsResource;

class WidgetsOverview extends StatsOverviewWidget
{
    public function getColumns(): int|array
    {
        return 2;
    }

    protected function getStats(): array
    {
        return [
            // URL for Customers only if has role super_admin
            Stat::make('Customers', Customer::count())
                ->url(auth()->user()?->hasRole('super_admin') ? CustomerResource::getUrl('index') : null),

            Stat::make('Workflows', Workflow::count())
                ->url(WorkflowResource::getUrl('index')),

            Stat::make('Tasks', Task::count())
                ->url(TaskResource::getUrl('index')),

            Stat::make('Jobs', JobManager::count())
                ->url(JobsResource::getUrl('index')),
        ];
    }
}
