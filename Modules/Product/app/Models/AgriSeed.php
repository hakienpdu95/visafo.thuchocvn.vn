<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgriSeed extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'crop_type',
        'name',
        'author_applicant',
        'decision_number',
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
