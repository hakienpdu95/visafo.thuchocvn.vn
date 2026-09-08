<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Warehouse\Enums\BatchDocumentType;

class BatchDocument extends Model
{
    use HasUlids;

    protected $fillable = [
        'batch_id',
        'document_code',
        'document_number',
        'file_url',
    ];

    protected function casts(): array
    {
        return [
            'document_code' => BatchDocumentType::class,
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
