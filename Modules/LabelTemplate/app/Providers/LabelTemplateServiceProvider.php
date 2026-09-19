<?php

namespace Modules\LabelTemplate\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\LabelTemplate\Models\LabelTemplate;
use Modules\LabelTemplate\Policies\LabelTemplatePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LabelTemplateServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'LabelTemplate';

    protected string $nameLower = 'labeltemplate';

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

        Gate::policy(LabelTemplate::class, LabelTemplatePolicy::class);
    }
}
