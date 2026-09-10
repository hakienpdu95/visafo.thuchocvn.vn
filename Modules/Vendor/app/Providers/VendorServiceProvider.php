<?php

namespace Modules\Vendor\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Policies\VendorPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class VendorServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Vendor';

    protected string $nameLower = 'vendor';

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

        Gate::policy(Vendor::class, VendorPolicy::class);
    }
}
