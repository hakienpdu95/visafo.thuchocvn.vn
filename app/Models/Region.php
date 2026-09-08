<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $fillable = ['name'];

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }
}
