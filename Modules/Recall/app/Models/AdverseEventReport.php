<?php

namespace Modules\Recall\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Modules\Recall\Enums\AdverseEventOutcome;
use Modules\Recall\Enums\AdverseEventReportSource;
use Modules\Recall\Enums\AdverseEventStatus;
use Modules\Recall\Enums\ConsumerGender;
use Modules\Warehouse\Models\Batch;

class AdverseEventReport extends TenantAwareModel
{
    protected $fillable = [
        'product_id',
        'batch_id',
        'lot_number_manual',
        'mfg_or_exp_date_manual',
        'company_name',
        'company_address',
        'reporter_name',
        'reporter_title',
        'reporter_phone',
        'reporter_fax',
        'reporter_email',
        'ingredients_packaging',
        'product_form_purpose',
        'manufacturer_origin',
        'consumer_name',
        'consumer_id_number',
        'consumer_age',
        'consumer_gender',
        'consumer_nationality',
        'onset_at',
        'reaction_description',
        'time_since_last_use',
        'usage_description',
        'was_hospitalized',
        'required_medical_treatment',
        'outcome',
        'outcome_date',
        'report_source',
        'report_source_detail',
        'received_at',
        'submitted_to_authority_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'consumer_age'                => 'integer',
            'consumer_gender'             => ConsumerGender::class,
            'onset_at'                    => 'datetime',
            'was_hospitalized'            => 'boolean',
            'required_medical_treatment'  => 'boolean',
            'outcome'                     => AdverseEventOutcome::class,
            'outcome_date'                => 'date',
            'report_source'               => AdverseEventReportSource::class,
            'received_at'                 => 'datetime',
            'submitted_to_authority_at'   => 'datetime',
            'status'                      => AdverseEventStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function lotNumber(): ?string
    {
        return $this->batch?->mfg_batch_number ?? $this->batch?->internal_batch_code ?? $this->lot_number_manual;
    }

    public function mfgOrExpDateLabel(): ?string
    {
        if ($this->batch) {
            $mfg = $this->batch->mfg_date?->format('d/m/Y');
            $exp = $this->batch->exp_date?->format('d/m/Y');

            return trim(($mfg ? "NSX {$mfg}" : '') . ($exp ? " — HSD {$exp}" : '')) ?: null;
        }

        return $this->mfg_or_exp_date_manual;
    }

    public function preliminaryDeadline(): \Illuminate\Support\Carbon
    {
        return $this->received_at->copy()->addDays(7);
    }

    public function detailedDeadline(): \Illuminate\Support\Carbon
    {
        return $this->received_at->copy()->addDays(7 + 8);
    }
}
