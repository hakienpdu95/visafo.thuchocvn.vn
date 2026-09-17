<?php

namespace Modules\SalesPackage\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\SalesPackage\Models\SalesPackage;
use Modules\SalesPackage\Policies\SalesPackagePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SalesPackageServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'SalesPackage';

    protected string $nameLower = 'salespackage';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(SalesPackage::class, SalesPackagePolicy::class);
    }
}
