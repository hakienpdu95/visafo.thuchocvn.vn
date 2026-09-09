<?php

namespace Modules\Contract\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Contract\Console\Commands\ProcessContractRenewalsCommand;
use Modules\Contract\Models\Contract;
use Modules\Contract\Policies\ContractPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ContractServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Contract';

    protected string $nameLower = 'contract';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        ProcessContractRenewalsCommand::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Contract::class, ContractPolicy::class);
    }

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('contracts:process-renewals')
            ->name('contracts:process-renewals')
            ->daily()
            ->onOneServer()
            ->withoutOverlapping();
    }
}
