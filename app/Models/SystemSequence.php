<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SystemSequence extends Model
{
    use HasUlids;

    protected $fillable = [
        'code_type',
        'prefix',
        'last_number',
        'padding_length',
    ];

    protected function casts(): array
    {
        return [
            'last_number'    => 'integer',
            'padding_length' => 'integer',
        ];
    }
}
