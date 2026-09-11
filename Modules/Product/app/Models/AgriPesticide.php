<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgriPesticide extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'category',
        'active_ingredients',
        'trade_name',
        'target_pest',
        'applicant',
        'quarantine_days',
        'is_banned',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quarantine_days' => 'integer',
            'is_banned'       => 'boolean',
        ];
    }
}
