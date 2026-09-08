<?php

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SapoProductSyncLogListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),

            'source'       => $this->source,
            'source_label' => $this->source === 'webhook' ? 'Webhook' : 'Polling định kỳ',

            'topic'       => $this->topic,
            'event_label' => match ($this->topic) {
                'products/create' => 'Thêm mới',
                'products/update' => 'Cập nhật',
                'products/delete' => 'Xóa',
                default            => $this->topic,
            },

            'product_name' => $this->product_name,
            'sku'          => $this->sku,
            'product_url'  => $this->product_id ? route('backend.products.show', $this->product_id) : null,

            'status'       => $this->status,
            'status_label' => $this->status === 'success' ? 'Thành công' : 'Lỗi',
            'status_badge' => $this->status === 'success' ? 'badge-success' : 'badge-error',

            'message' => $this->message,
        ];
    }
}
