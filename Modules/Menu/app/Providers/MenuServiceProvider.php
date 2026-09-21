<?php

namespace Modules\Menu\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Menu\Models\Menu;
use Modules\Menu\Policies\MenuPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MenuServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Menu';

    protected string $nameLower = 'menu';

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

        Gate::policy(Menu::class, MenuPolicy::class);
    }
}
