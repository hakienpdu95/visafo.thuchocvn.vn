<?php

namespace Modules\Contract\Http\Resources;

use App\Services\Media\MediaUrlService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyContractRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mediaUrl = app(MediaUrlService::class);

        return [
            'contract_number' => $this->contract_number,
            'name'            => $this->name,
            'contract_type'   => $this->contractType?->name,
            'total_value'     => $this->total_value,
            'start_date'      => $this->start_date?->format('d/m/Y'),
            'end_date'        => $this->end_date?->format('d/m/Y'),
            'is_auto_renew'   => (bool) $this->is_auto_renew,
            'status_label'    => $this->status->label(),
            'status_badge'    => $this->status->badgeClass(),
            'files'           => $this->media
                ->where('collection_name', 'attachments_private')
                ->map(fn ($m) => ['name' => $m->file_name, 'url' => $mediaUrl->url($m)])
                ->values(),
            'show_url'        => route('backend.contracts.show', $this->resource),
            'edit_url'        => $request->user()?->can('update', $this->resource) ? route('backend.contracts.edit', $this->resource) : null,
        ];
    }
}
