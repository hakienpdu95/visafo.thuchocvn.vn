<?php

namespace Modules\GoodsReceipt\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\GoodsReceipt\Policies\GoodsReceiptPolicy;
use Modules\GoodsReceipt\Policies\ProductBatchPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class GoodsReceiptServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'GoodsReceipt';

    protected string $nameLower = 'goodsreceipt';

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

        Gate::policy(GoodsReceipt::class, GoodsReceiptPolicy::class);
        Gate::policy(ProductBatch::class, ProductBatchPolicy::class);
    }
}
