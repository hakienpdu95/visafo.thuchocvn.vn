<?php

namespace Modules\SalesOrder\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Policies\SalesOrderPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SalesOrderServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'SalesOrder';

    protected string $nameLower = 'salesorder';

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

        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
    }
}
