<?php

namespace Modules\HanoiCheckOrder\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\HanoiCheckOrder\Console\Commands\HanoiCheckGoLiveCommand;
use Modules\HanoiCheckOrder\Console\Commands\RefreshHanoiCheckOrdersCommand;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\HanoiCheckOrder\Models\HanoiCheckTransporter;
use Modules\HanoiCheckOrder\Observers\PrintLogHanoiCheckObserver;
use Modules\HanoiCheckOrder\Observers\SalesOrderHanoiCheckObserver;
use Modules\HanoiCheckOrder\Policies\HanoiCheckOrderLinkPolicy;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HanoiCheckOrderServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'HanoiCheckOrder';

    protected string $nameLower = 'hanoicheckorder';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        RefreshHanoiCheckOrdersCommand::class,
        HanoiCheckGoLiveCommand::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(HanoiCheckOrderLink::class, HanoiCheckOrderLinkPolicy::class);
        Gate::policy(HanoiCheckTransporter::class, HanoiCheckOrderLinkPolicy::class);

        SalesOrder::observe(SalesOrderHanoiCheckObserver::class);
        PrintLog::observe(PrintLogHanoiCheckObserver::class);
    }

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('hanoicheck-orders:refresh')
            ->name('hanoicheck-orders:refresh')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->onOneServer();
    }
}
