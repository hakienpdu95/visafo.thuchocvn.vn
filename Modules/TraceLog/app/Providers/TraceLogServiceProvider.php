<?php

namespace Modules\TraceLog\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\SalesOrder\Models\PrintLog;
use Modules\TraceLog\Policies\TraceLogPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TraceLogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'TraceLog';

    protected string $nameLower = 'tracelog';

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

        Gate::policy(PrintLog::class, TraceLogPolicy::class);
    }
}
