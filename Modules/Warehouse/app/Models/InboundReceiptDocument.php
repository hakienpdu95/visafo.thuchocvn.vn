<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Warehouse\Enums\InboundDocumentType;

class InboundReceiptDocument extends Model
{
    use HasUlids;

    protected $fillable = [
        'inbound_receipt_id',
        'document_code',
        'document_number',
        'file_url',
    ];

    protected function casts(): array
    {
        return [
            'document_code' => InboundDocumentType::class,
        ];
    }

    public function inboundReceipt(): BelongsTo
    {
        return $this->belongsTo(InboundReceipt::class);
    }
}
