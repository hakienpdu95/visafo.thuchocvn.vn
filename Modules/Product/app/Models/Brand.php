<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

class Brand extends TenantAwareModel implements HasMedia
{
    use HasTenantMedia;

    protected $fillable = [
        'name',
        'description',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
