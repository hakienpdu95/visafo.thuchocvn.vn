<?php

namespace Modules\HanoiCheckOrder\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SalesOrder\Models\SalesOrder;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class HanoiCheckOrderLink extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $table = 'hanoicheck_order_links';

    protected $fillable = ['sales_order_id', 'hanoicheck_order_code', 'note', 'linked_by', 'linked_at'];

    protected function casts(): array
    {
        return ['linked_at' => 'datetime'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class)->withTrashed();
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }
}
