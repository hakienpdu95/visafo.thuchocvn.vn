<?php

namespace Modules\Recall\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Recall\Models\AdverseEventReport;
use Modules\Recall\Models\ProductRecall;
use Modules\Recall\Policies\AdverseEventReportPolicy;
use Modules\Recall\Policies\ProductRecallPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class RecallServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Recall';

    protected string $nameLower = 'recall';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(ProductRecall::class, ProductRecallPolicy::class);
        Gate::policy(AdverseEventReport::class, AdverseEventReportPolicy::class);
    }
}
