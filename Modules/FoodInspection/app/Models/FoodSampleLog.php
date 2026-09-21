<?php

namespace Modules\FoodInspection\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerDeliveryPoint;
use Modules\FoodInspection\Enums\SampleStatus;

/** Sổ lưu và hủy mẫu thức ăn (Mẫu số 4 & 5, Phụ lục 1 QĐ 1246/QĐ-BYT). */
class FoodSampleLog extends TenantAwareModel
{
    protected $table = 'food_sample_logs';

    protected $fillable = [
        'customer_id', 'customer_name', 'delivery_point_id', 'location_name',
        'sample_date', 'status', 'note', 'created_by', 'creator_name',
    ];

    protected function casts(): array
    {
        return [
            'sample_date' => 'date',
            'status'      => SampleStatus::class,
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(FoodSampleDetail::class, 'log_id')->orderBy('line_no');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryPoint(): BelongsTo
    {
        return $this->belongsTo(CustomerDeliveryPoint::class, 'delivery_point_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
