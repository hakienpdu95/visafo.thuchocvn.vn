<?php

namespace Modules\FoodInspection\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;

/** Sổ kiểm thực Bước 1 — kiểm tra trước khi chế biến (Mẫu số 1, Phụ lục 1 QĐ 1246/QĐ-BYT). */
class FoodInspectionStep1Log extends TenantAwareModel
{
    protected $table = 'food_inspection_step1_logs';

    protected $fillable = [
        'customer_id',
        'customer_name',
        'inspected_at',
        'inspection_location',
        'attachments',
        'note',
        'failed_items_count',
        'inspected_by',
    ];

    protected function casts(): array
    {
        return [
            'inspected_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(FoodInspectionStep1Detail::class, 'log_id')->orderBy('food_group')->orderBy('line_no');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
