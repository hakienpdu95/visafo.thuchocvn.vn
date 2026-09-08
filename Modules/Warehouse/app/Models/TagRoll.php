<?php

namespace Modules\Warehouse\Models;

use App\Models\User;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Warehouse\Enums\RetailItemTagStatus;

class TagRoll extends Model
{
    use HasUlids;
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'prefix',
        'from_sequence',
        'to_sequence',
        'count',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_sequence' => 'integer',
            'to_sequence'   => 'integer',
            'count'         => 'integer',
            'created_at'    => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tagsQuery()
    {
        return RetailItemTag::whereBetween('visual_sequence', [$this->from_sequence, $this->to_sequence]);
    }

    /** @return array{provisioned: int, bound: int} */
    public function liveCounts(): array
    {
        $provisioned = $this->tagsQuery()->where('status', RetailItemTagStatus::Provisioned->value)->count();

        return [
            'provisioned' => $provisioned,
            'bound'       => $this->count - $provisioned,
        ];
    }
}
