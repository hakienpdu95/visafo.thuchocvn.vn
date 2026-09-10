<?php

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    use HasUlids;

    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'name',
        'title',
        'phone',
        'email',
        'note',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
