<?php

namespace Modules\Compliance\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Modules\Compliance\Console\Commands\ScanComplianceWarningsCommand;
use Modules\Compliance\Models\ComplianceWarning;
use Modules\Compliance\Policies\ComplianceWarningPolicy;
use Modules\Employee\Models\EmployeeHealthRecord;
use Modules\Product\Models\ProductCompliance;
use Modules\Vendor\Models\VendorCertificate;
use Modules\Warehouse\Models\Batch;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ComplianceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Compliance';

    protected string $nameLower = 'compliance';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        ScanComplianceWarningsCommand::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Relation::morphMap([
            'product_compliance'      => ProductCompliance::class,
            'vendor_certificate'      => VendorCertificate::class,
            'batch'                   => Batch::class,
            'employee_health_record'  => EmployeeHealthRecord::class,
        ]);

        Gate::policy(ComplianceWarning::class, ComplianceWarningPolicy::class);
    }

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('compliance:scan-warnings')
            ->name('compliance:scan-warnings')
            ->dailyAt('06:00')
            ->onOneServer();
    }
}
