<?php

namespace Modules\Compliance\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Modules\Compliance\Console\Commands\ScanComplianceWarningsCommand;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\ComplianceWarning;
use Modules\Compliance\Models\InternalFacility;
use Modules\Compliance\Observers\ComplianceDocumentObserver;
use Modules\Compliance\Policies\ComplianceDocumentPolicy;
use Modules\Compliance\Policies\ComplianceWarningPolicy;
use Modules\Compliance\Policies\InternalFacilityPolicy;
use Modules\Employee\Models\EmployeeHealthRecord;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;
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
            'employee_health_record' => EmployeeHealthRecord::class,
            'compliance_document'    => ComplianceDocument::class,
            'vendor'                 => Vendor::class,
            'product'                => Product::class,
            'partner_product'        => PartnerProduct::class,
            'internal_facility'      => InternalFacility::class,
        ]);

        Gate::policy(ComplianceWarning::class, ComplianceWarningPolicy::class);
        Gate::policy(ComplianceDocument::class, ComplianceDocumentPolicy::class);
        Gate::policy(InternalFacility::class, InternalFacilityPolicy::class);

        ComplianceDocument::observe(ComplianceDocumentObserver::class);
    }

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('compliance:scan-warnings')
            ->name('compliance:scan-warnings')
            ->dailyAt('06:00')
            ->onOneServer();
    }
}
