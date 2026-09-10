<?php

namespace Modules\Product\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Product\Console\Commands\CheckExpiringCompliancesCommand;
use Modules\Product\Models\Category;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\PartnerProductCompliance;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCompliance;
use Modules\Product\Observers\ProductComplianceObserver;
use Modules\Product\Policies\CategoryPolicy;
use Modules\Product\Policies\DocumentMasterTypePolicy;
use Modules\Product\Policies\PartnerProductCompliancePolicy;
use Modules\Product\Policies\PartnerProductPolicy;
use Modules\Product\Policies\ProductCompliancePolicy;
use Modules\Product\Policies\ProductPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProductServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Product';

    protected string $nameLower = 'product';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        CheckExpiringCompliancesCommand::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(DocumentMasterType::class, DocumentMasterTypePolicy::class);
        Gate::policy(ProductCompliance::class, ProductCompliancePolicy::class);
        Gate::policy(PartnerProduct::class, PartnerProductPolicy::class);
        Gate::policy(PartnerProductCompliance::class, PartnerProductCompliancePolicy::class);

        ProductCompliance::observe(ProductComplianceObserver::class);
    }

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('product:compliances-expiry-check')
            ->name('product:compliances-expiry-check')
            ->dailyAt('00:30')
            ->onOneServer();
    }
}
