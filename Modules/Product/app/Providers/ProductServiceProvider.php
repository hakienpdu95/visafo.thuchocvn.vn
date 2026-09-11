<?php

namespace Modules\Product\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Product\Models\AgriFertilizer;
use Modules\Product\Models\AgriPesticide;
use Modules\Product\Models\AgriSeed;
use Modules\Product\Models\Category;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\Product\Policies\AgriFertilizerPolicy;
use Modules\Product\Policies\AgriPesticidePolicy;
use Modules\Product\Policies\AgriSeedPolicy;
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
        Gate::policy(AgriPesticide::class, AgriPesticidePolicy::class);
        Gate::policy(AgriFertilizer::class, AgriFertilizerPolicy::class);
        Gate::policy(AgriSeed::class, AgriSeedPolicy::class);
    }
}
