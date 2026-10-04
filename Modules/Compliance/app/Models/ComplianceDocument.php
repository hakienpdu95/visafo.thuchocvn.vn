<?php

namespace Modules\Compliance\Models;

use App\Traits\HasCreator;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Enums\SharedDocumentCategory;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Models\DocumentMasterType;
use Spatie\MediaLibrary\HasMedia;

class ComplianceDocument extends Model implements HasMedia
{
    use HasCreator;

    use HasUlids;
    use SoftDeletes;
    use HasTenantMedia;

    public $timestamps = true;

    protected $fillable = [
        'document_master_type_id',
        'document_number',
        'classification_grade',
        'issue_date',
        'expiration_date',
        'issued_by',
        'status',
        'notes',
        'custom_name',
        'custom_category',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'       => 'date',
            'expiration_date'  => 'date',
            'status'           => ComplianceDocumentStatus::class,
            'custom_category'  => SharedDocumentCategory::class,
        ];
    }

    public function isShared(): bool
    {
        return $this->documentable_type === null;
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentMasterType::class, 'document_master_type_id');
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->isPast();
    }

    public function isExpiringWithinDays(int $days): bool
    {
        return $this->expiration_date !== null
            && ! $this->isExpired()
            && $this->expiration_date->lte(now()->addDays($days));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiration_date')->where('expiration_date', '<', now());
    }

    public function scopeExpiringWithinDays(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '>=', now())
            ->where('expiration_date', '<=', now()->addDays($days));
    }

    /**
     * Hồ sơ doanh nghiệp (của Trụ sở chính / các cơ sở nội bộ) được phép công bố trên trang truy xuất công khai:
     * thuộc nhóm "Hồ sơ pháp lý cơ sở", đang hiệu lực, chưa hết hạn, và loại giấy tờ được bật
     * "Công khai trên trang truy xuất" (is_public — cờ kiểm duyệt, chặn giấy tờ chứa thông tin cá nhân như giấy ủy quyền).
     */
    public function scopePublicCompanyProfile(Builder $query): Builder
    {
        return $query
            ->where('documentable_type', (new InternalFacility())->getMorphClass())
            ->where('status', ComplianceDocumentStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))
            ->whereHas('documentType', fn (Builder $q) => $q
                ->where('document_group', DocumentGroupType::LegalFacility->value)
                ->where('is_public', true));
    }

    public function permissionModule(): string
    {
        return 'compliance';
    }
}
