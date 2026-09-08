<?php

namespace Modules\ActivityLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Modules\ActivityLog\Enums\LogLevel;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    use HasUlids;

    protected $table = 'activity_log';

    protected $casts = [
        'level'      => LogLevel::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * SRS01-FR-OPS-003 (GAP_ANALYSIS_v1.0.md §3.3 OPS-03): audit_events phải
     * append-only. Enforce ở tầng ORM (mọi write của app đi qua Eloquent) —
     * chặn UPDATE/DELETE sau khi record đã tồn tại. Không thay được việc revoke
     * UPDATE/DELETE privilege ở DB user (hạ tầng/deploy, ngoài phạm vi code).
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \RuntimeException('activity_log là append-only — không được UPDATE record đã tồn tại (SRS01-FR-OPS-003).');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \RuntimeException('activity_log là append-only — không được DELETE record (SRS01-FR-OPS-003).');
    }

    // ── Relationships ─────────────────────────────────────────────

    public function onBehalfOfUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'on_behalf_of');
    }

    public function contexts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityLogContext::class, 'log_id');
    }

    public function http(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ActivityLogHttp::class, 'log_id');
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getContextMapAttribute(): array
    {
        return $this->contexts
            ->mapWithKeys(fn($c) => [$c->key_name => $c->typedValue()])
            ->all();
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeForOrganization(Builder $q, string $orgId): Builder
    {
        // Bao gồm cả rows không có org (system/legacy logs)
        return $q->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'));
    }

    public function scopeModule(Builder $q, string $module): Builder
    {
        return $q->where('module', $module);
    }

    public function scopeAction(Builder $q, string $action): Builder
    {
        return $q->where('action', $action);
    }

    public function scopeLevel(Builder $q, int $min): Builder
    {
        return $q->where('level', '>=', $min);
    }

    /**
     * Lọc theo subject — dùng FQCN (get_class) để khớp với giá trị lưu trong DB.
     */
    public function scopeForSubject(Builder $q, Model $model): Builder
    {
        return $q->where('subject_type', get_class($model))
                 ->where('subject_id', $model->getKey());
    }
}
