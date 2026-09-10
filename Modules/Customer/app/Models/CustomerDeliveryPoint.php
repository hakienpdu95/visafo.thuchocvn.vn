<?php

namespace Modules\Customer\Models;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDeliveryPoint extends Model
{
    use HasUlids;

    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'site_name',
        'address',
        'province_code',
        'ward_code',
        'receiver_name',
        'receiver_phone',
        'note',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_code', 'ward_code');
    }
}
