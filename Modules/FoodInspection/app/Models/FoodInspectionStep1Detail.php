<?php

namespace Modules\FoodInspection\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FoodInspection\Enums\FoodGroup;
use Modules\FoodInspection\Enums\InspectionResult;
use Modules\FoodInspection\Enums\QuickTestResult;
use Modules\FoodInspection\Enums\StorageCondition;

class FoodInspectionStep1Detail extends TenantAwareModel
{
    protected $table = 'food_inspection_step1_details';

    protected $fillable = [
        'log_id',
        'product_id',
        'vendor_id',
        'vendor_name',
        'supplier_address',
        'supplier_phone',
        'supplier_address_phone',
        'deliverer_name',
        'received_at',
        'line_no',
        'food_group',
        'product_name',
        'quantity',
        'unit',
        'has_invoice',
        'has_vet_cert',
        'has_quarantine_cert',
        'sensory_result',
        'quick_test_result',
        'handling_measure',
        'manufacturer_name',
        'manufacturer_address',
        'expiry_date',
        'storage_condition',
    ];

    protected function casts(): array
    {
        return [
            'food_group'          => FoodGroup::class,
            'sensory_result'      => InspectionResult::class,
            'quick_test_result'   => QuickTestResult::class,
            'storage_condition'   => StorageCondition::class,
            'received_at'         => 'datetime',
            'quantity'            => 'decimal:3',
            'has_invoice'         => 'boolean',
            'has_vet_cert'        => 'boolean',
            'has_quarantine_cert' => 'boolean',
            'expiry_date'         => 'date',
        ];
    }

    public function isFailed(): bool
    {
        return $this->sensory_result === InspectionResult::Fail || $this->quick_test_result === QuickTestResult::Fail;
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(FoodInspectionStep1Log::class, 'log_id');
    }
}
