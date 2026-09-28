<?php

namespace Modules\Contract\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Contract\Console\Commands\MarkExpiredContractsCommand;
use Modules\Contract\Console\Commands\ProcessContractRenewalsCommand;
use Modules\Contract\Models\Contract;
use Modules\Contract\Models\VendorComplianceRequirement;
use Modules\Contract\Observers\VendorComplianceCacheObserver;
use Modules\Contract\Policies\ContractPolicy;
use Modules\Contract\Policies\VendorComplianceRequirementPolicy;
use Modules\Vendor\Models\Vendor;
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
        MarkExpiredContractsCommand::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(VendorComplianceRequirement::class, VendorComplianceRequirementPolicy::class);

        foreach ([Contract::class, ComplianceDocument::class, Vendor::class, VendorComplianceRequirement::class] as $model) {
            $model::observe(VendorComplianceCacheObserver::class);
        }
    }

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('contracts:process-renewals')
            ->name('contracts:process-renewals')
            ->daily()
            ->onOneServer()
            ->withoutOverlapping();

        $schedule->command('contracts:mark-expired')
            ->name('contracts:mark-expired')
            ->dailyAt('00:00')
            ->onOneServer()
            ->withoutOverlapping();
    }
}
