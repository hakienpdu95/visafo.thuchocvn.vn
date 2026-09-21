<?php

namespace Modules\FoodInspection\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\FoodInspection\Enums\SampleStatus;
use Modules\FoodInspection\Models\FoodSampleDetail;

class FoodSampleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $destroyed = $this->status === SampleStatus::Destroyed;
        $pending = ! $destroyed && (bool) $this->has_pending;

        [$label, $badge] = match (true) {
            $destroyed => ['Đã hủy', 'badge-neutral'],
            $pending   => ['Chờ hủy mẫu', 'badge-warning'],
            default    => ['Lưu mẫu', 'badge-info'],
        };

        // Mốc sớm nhất có thể hủy trong các mẫu chưa hủy.
        $due = ! $destroyed && $this->first_sampled_at
            ? Carbon::parse($this->first_sampled_at)->addHours(FoodSampleDetail::MIN_RETENTION_HOURS)->format('d/m/Y H:i')
            : null;

        return [
            'id'            => $this->id,
            'sample_date'   => $this->sample_date?->format('d/m/Y'),
            'customer_name' => $this->customer_name,
            'location_name' => $this->location_name,
            'details_count' => (int) $this->details_count,
            'status_label'  => $label,
            'status_badge'  => $badge,
            'due_at'        => $due,
            'creator_name'  => $this->creator_name,
            'show_url'      => route('backend.food-samples.show', $this->resource),
        ];
    }
}
