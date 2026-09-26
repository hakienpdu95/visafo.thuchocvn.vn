<?php

namespace Modules\FoodInspection\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerDeliveryPoint;

/** Sổ kiểm thực Bước 3 — kiểm tra trước khi ăn (Mẫu số 3, Phụ lục 1 QĐ 1246/QĐ-BYT). */
class FoodInspectionStep3Log extends TenantAwareModel
{
    use HasCreator;

    protected $table = 'food_inspection_step3_logs';

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
        return $this->hasMany(FoodInspectionStep3Detail::class, 'log_id')->orderBy('line_no');
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

    public function permissionModule(): string
    {
        return 'food_inspection';
    }
}
