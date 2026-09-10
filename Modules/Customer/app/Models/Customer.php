<?php

namespace Modules\Customer\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Enums\CustomerGroup;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\MealModel;
use Modules\Employee\Models\Employee;
use Modules\Product\Models\Product;

class Customer extends TenantAwareModel
{
    protected $fillable = [
        'customer_code',
        'name',
        'customer_group',
        'meal_model',
        'tax_code',
        'address',
        'province_code',
        'ward_code',
        'phone_number',
        'email',
        'representative_name',
        'representative_title',
        'representative_phone',
        'representative_email',
        'pic_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'customer_group' => CustomerGroup::class,
            'meal_model'     => MealModel::class,
            'status'         => CustomerStatus::class,
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_code', 'ward_code');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function deliveryPoints(): HasMany
    {
        return $this->hasMany(CustomerDeliveryPoint::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->using(CustomerProduct::class)
            ->withPivot('status')
            ->withTimestamps();
    }
}
