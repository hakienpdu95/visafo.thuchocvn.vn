<?php

namespace Modules\SalesPackage\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;
use Modules\SalesPackage\Enums\SalesPackageStatus;

class SalesPackage extends TenantAwareModel
{
    protected $fillable = [
        'customer_id',
        'name',
        'expected_deadline',
        'version',
        'readiness_score',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expected_deadline' => 'datetime',
            'version'           => 'integer',
            'readiness_score'   => 'integer',
            'status'            => SalesPackageStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesPackageItem::class);
    }
}
