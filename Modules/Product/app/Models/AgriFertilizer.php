<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgriFertilizer extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'category',
        'name',
        'ingredients',
        'applicant',
        'is_banned',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_banned' => 'boolean',
        ];
    }
}
