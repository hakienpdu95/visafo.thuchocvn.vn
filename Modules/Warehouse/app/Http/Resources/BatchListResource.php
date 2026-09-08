<?php

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status     = $this->status;
        $product    = $this->product;
        $compliance = $product?->latestCompliance;

        return [
            'id'                  => $this->id,
            'internal_batch_code' => $this->internal_batch_code,
            'mfg_batch_number'    => $this->mfg_batch_number,

            'product_name' => $product?->name,
            'product_sku'  => $product?->sku,
            'vendor_name'  => $this->vendor?->name,

            'mfg_date' => $this->mfg_date?->format('d/m/Y'),
            'exp_date' => $this->exp_date?->format('d/m/Y'),

            'is_expired'  => $this->isExpired(),
            'is_expiring' => $this->isExpiringWithinDays(30),

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'current_qty'         => $this->current_qty,
            'initial_qty'         => $this->initial_qty,
            'tags_count'          => $this->tags_count,
            'tags_exported_count' => $this->tags_exported_count,

            'compliance_document_number' => $compliance?->document_number,
            'compliance_file_url'        => $compliance?->file_url,
            'compliance_create_url'      => $product ? route('backend.products.show', $product) . '#add-compliance' : null,

            'adverse_event_reports_count' => $this->adverse_event_reports_count,

            'show_url'            => route('backend.batches.show', $this->resource),
            'inbound_receipt_url' => route('backend.inbound-receipts.show', $this->inboundReceipt),
            'incidents_url'       => route('backend.batches.incidents', $this->resource),
            'audit_trail_url'     => route('backend.batches.audit-trail', $this->resource),
            'sapo_sync_log_url'   => route('backend.batches.sapo-sync-log', $this->resource),
            'recall_url'          => route('backend.batches.recall', $this->resource),

            'is_recalled' => $status->value === 'recalled',
        ];
    }
}
