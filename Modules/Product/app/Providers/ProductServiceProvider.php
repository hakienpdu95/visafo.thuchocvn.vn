<?php

namespace Modules\Product\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Product\Models\Category;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\Product\Policies\CategoryPolicy;
use Modules\Product\Policies\DocumentMasterTypePolicy;
use Modules\Product\Policies\PartnerProductPolicy;
use Modules\Product\Policies\ProductPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProductServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Product';

    protected string $nameLower = 'product';

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

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(DocumentMasterType::class, DocumentMasterTypePolicy::class);
        Gate::policy(PartnerProduct::class, PartnerProductPolicy::class);
    }
}
