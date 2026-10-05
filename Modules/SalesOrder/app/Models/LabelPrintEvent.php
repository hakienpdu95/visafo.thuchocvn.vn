<?php

namespace Modules\SalesOrder\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LabelPrintEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'print_log_id',
        'order_item_id',
        'print_session_id',
        'user_id',
        'quantity',
        'is_reprint',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'is_reprint' => 'boolean',
            'printed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public static function record(iterable $logs, string $sessionId, ?string $userId, bool $isReprint): void
    {
        $now = now();

        $rows = Collection::make($logs)->map(fn (PrintLog $log) => [
            'id'               => Str::lower((string) Str::ulid()),
            'print_log_id'     => $log->id,
            'order_item_id'    => $log->order_item_id,
            'print_session_id' => $sessionId,
            'user_id'          => $userId,
            'quantity'         => 1,
            'is_reprint'       => $isReprint,
            'printed_at'       => $now,
        ]);

        $rows->chunk(500)->each(fn (Collection $chunk) => static::query()->insert($chunk->values()->all()));
    }

    public function printLog(): BelongsTo
    {
        return $this->belongsTo(PrintLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
