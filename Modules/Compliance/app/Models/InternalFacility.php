<?php

namespace Modules\Compliance\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Compliance\Enums\CompanyType;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** HasMedia: ảnh chèn qua Jodit trong "Vai trò trong chuỗi cung ứng" (collection jodit_content) gắn vào trụ sở chính. */
class InternalFacility extends TenantAwareModel implements HasMedia
{
    use HasCreator;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'type',
        'address',
        'status',
        'company_name',
        'company_type',
        'tax_code',
        'tax_code_issue_date',
        'tax_code_issue_place',
        'province_code',
        'ward_code',
        'supply_chain_role',
    ];

    protected function casts(): array
    {
        return [
            'company_type'        => CompanyType::class,
            'tax_code_issue_date' => 'date',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_code', 'ward_code');
    }

    /**
     * Giá trị cố định (config/company.php) của các trường pháp lý/địa chỉ trụ sở — form chỉ hiển thị,
     * server luôn ghi đè bằng các giá trị này.
     *
     * @return array<string, string>
     */
    public static function lockedCompanyValues(): array
    {
        return array_filter((array) config('company.locked'), fn ($v) => filled($v));
    }

    /** Địa chỉ đầy đủ: số nhà/đường, Phường/Xã, Tỉnh/TP (bỏ phần trống). */
    public function fullAddress(): ?string
    {
        // Địa chỉ nhập có thể đã ghi sẵn tên phường/tỉnh (VD "…, Xã Phúc Thịnh, Thành phố Hà Nội, Việt Nam") → không nối lặp
        $parts = array_filter([$this->address], fn ($v) => filled($v));
        foreach ([$this->ward?->name, $this->province?->name] as $name) {
            if (filled($name) && ! str_contains(mb_strtolower((string) $this->address), mb_strtolower($name))) {
                $parts[] = $name;
            }
        }

        return $parts ? implode(', ', $parts) : null;
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ComplianceDocument::class, 'documentable');
    }

    public function activeDocuments(): MorphMany
    {
        return $this->documents()->where('status', ComplianceDocumentStatus::Active->value);
    }

    public function latestDocument(): MorphOne
    {
        return $this->morphOne(ComplianceDocument::class, 'documentable')->ofMany('issue_date', 'max');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'headquarter'      => 'Trụ sở chính',
            'farm'              => 'Vùng trồng',
            'warehouse'         => 'Kho',
            'processing_zone'   => 'Khu sơ chế/chế biến',
            default             => $this->type,
        };
    }

    public function permissionModule(): string
    {
        return 'compliance';
    }
}
