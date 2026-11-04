<?php

namespace Modules\HanoiCheck\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Enums\SyncStatus;

class HanoiCheckSyncLog extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'hanoicheck_sync_logs';

    protected $fillable = [
        'entity_type', 'local_id', 'status', 'attempt', 'environment', 'http_method', 'endpoint',
        'request_payload', 'payload_hash', 'http_status', 'response_body', 'error_code', 'request_nonce', 'error_message', 'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'entity_type'     => SyncEntity::class,
            'status'          => SyncStatus::class,
            'request_payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Nhật ký đồng bộ HanoiCheck là bất biến, không được sửa.'));
        static::deleting(fn () => throw new LogicException('Nhật ký đồng bộ HanoiCheck phải lưu tối thiểu 2 năm, không được xóa.'));
    }
}
