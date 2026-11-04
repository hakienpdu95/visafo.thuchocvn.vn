<?php

namespace Modules\HanoiCheckOrder\Models;

use App\Models\User;
use App\Services\Media\MediaUrlService;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HanoiCheckOrder\Enums\OrderSyncStatus;
use Modules\HanoiCheckOrder\Enums\RemoteOrderStatus;
use Modules\SalesOrder\Models\SalesOrder;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

class HanoiCheckOrderLink extends Model implements HasMedia
{
    use HasTenantMedia;
    use HasUlids;
    use LogsActivity;

    public const PHOTO_COLLECTION = 'hanoicheck_delivery';

    public const MAX_PHOTOS = 3;

    protected $table = 'hanoicheck_order_links';

    protected $fillable = [
        'sales_order_id', 'hanoicheck_order_code', 'note', 'linked_by', 'linked_at', 'transporter_id',
        'remote_status', 'school_name', 'sync_status', 'sync_message', 'process_hash', 'snapshot', 'fetched_at', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'linked_at'     => 'datetime',
            'fetched_at'    => 'datetime',
            'synced_at'     => 'datetime',
            'snapshot'      => 'array',
            'remote_status' => RemoteOrderStatus::class,
            'sync_status'   => OrderSyncStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['hanoicheck_order_code', 'note', 'transporter_id', 'remote_status'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class)->withTrashed();
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(HanoiCheckTransporter::class, 'transporter_id');
    }

    public function photos(): array
    {
        $urls = app(MediaUrlService::class);

        return $this->getMedia(self::PHOTO_COLLECTION)
            ->map(fn ($media) => ['id' => $media->id, 'url' => $urls->url($media), 'name' => $media->file_name])
            ->values()->all();
    }
}
