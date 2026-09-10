<?php

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomerProduct extends Pivot
{
    use HasUlids;

    protected $table = 'customer_product';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'product_id',
        'status',
    ];
}
