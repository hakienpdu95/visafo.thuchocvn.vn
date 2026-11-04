<?php

namespace Modules\HanoiCheckOrder\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\HanoiCheckOrder\Policies\HanoiCheckOrderLinkPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HanoiCheckOrderServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'HanoiCheckOrder';

    protected string $nameLower = 'hanoicheckorder';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(HanoiCheckOrderLink::class, HanoiCheckOrderLinkPolicy::class);
    }
}
