<?php

namespace Modules\Warehouse\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Warehouse\Console\Commands\ProvisionTagsCommand;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\InboundReceipt;
use Modules\Warehouse\Models\OutboundOrder;
use Modules\Warehouse\Observers\BatchObserver;
use Modules\Warehouse\Policies\BatchPolicy;
use Modules\Warehouse\Policies\InboundReceiptPolicy;
use Modules\Warehouse\Policies\OutboundOrderPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class WarehouseServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Warehouse';

    protected string $nameLower = 'warehouse';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        ProvisionTagsCommand::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(InboundReceipt::class, InboundReceiptPolicy::class);
        Gate::policy(Batch::class, BatchPolicy::class);
        Gate::policy(OutboundOrder::class, OutboundOrderPolicy::class);

        Batch::observe(BatchObserver::class);
    }
}
