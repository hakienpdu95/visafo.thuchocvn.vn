<?php

namespace Modules\FoodInspection\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;
use Modules\FoodInspection\Models\FoodSampleLog;
use Modules\FoodInspection\Models\FoodInspectionStep2Log;
use Modules\FoodInspection\Models\FoodInspectionStep3Log;
use Modules\FoodInspection\Policies\FoodInspectionStep1LogPolicy;
use Modules\FoodInspection\Policies\FoodSampleLogPolicy;
use Modules\FoodInspection\Policies\FoodInspectionStep2LogPolicy;
use Modules\FoodInspection\Policies\FoodInspectionStep3LogPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class FoodInspectionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'FoodInspection';

    protected string $nameLower = 'foodinspection';

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

        Gate::policy(FoodInspectionStep1Log::class, FoodInspectionStep1LogPolicy::class);
        Gate::policy(FoodInspectionStep2Log::class, FoodInspectionStep2LogPolicy::class);
        Gate::policy(FoodInspectionStep3Log::class, FoodInspectionStep3LogPolicy::class);
        Gate::policy(FoodSampleLog::class, FoodSampleLogPolicy::class);
    }
}
