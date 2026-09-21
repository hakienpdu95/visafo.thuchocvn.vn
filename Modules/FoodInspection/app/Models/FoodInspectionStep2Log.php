<?php

namespace Modules\FoodInspection\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerDeliveryPoint;

/** Sổ kiểm thực Bước 2 — kiểm tra khi chế biến (Mẫu số 2, Phụ lục 1 QĐ 1246/QĐ-BYT). */
class FoodInspectionStep2Log extends TenantAwareModel
{
    protected $table = 'food_inspection_step2_logs';

    protected $fillable = [
        'customer_id', 'customer_name', 'delivery_point_id', 'location_name',
        'inspection_date', 'note', 'failed_items_count', 'inspected_by', 'inspector_name',
    ];

    protected function casts(): array
    {
        return ['inspection_date' => 'date'];
    }

    public function details(): HasMany
    {
        return $this->hasMany(FoodInspectionStep2Detail::class, 'log_id')->orderBy('line_no');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryPoint(): BelongsTo
    {
        return $this->belongsTo(CustomerDeliveryPoint::class, 'delivery_point_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
