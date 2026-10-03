<?php

namespace Modules\Product\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use App\Traits\HasAutoCode;
use App\Traits\HasTenantMedia;
use App\Models\Media;
use App\Services\Media\MediaUrlService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerProduct;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Spatie\MediaLibrary\HasMedia;

class Product extends TenantAwareModel implements HasMedia
{
    use HasCreator;

    use HasAutoCode;

    use HasTenantMedia;

    /** Bộ sưu tập ảnh sản phẩm; ảnh chính luôn nằm ở order_column nhỏ nhất. */
    public const GALLERY_COLLECTION = 'gallery';

    public function autoCodeColumn(): string
    {
        return 'sku';
    }

    public function autoCodeSequenceType(): string
    {
        return 'product';
    }

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'product_type',
        'unit',
        'shelf_life_days',
        'external_product_id',
        'sapo_product_id',
        'sapo_variant_id',
        'image_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'status'       => ProductStatus::class,
            'shelf_life_days' => 'integer',
        ];
    }

    /**
     * HSD tự tính = ngày gốc (NSX, hoặc ngày nhập nếu không có NSX) + shelf_life_days.
     * Null khi sản phẩm không cấu hình số ngày bảo quản (dùng HSD in trên bao bì).
     */
    public function calculateExpDate(\Illuminate\Support\Carbon|string|null $baseDate): ?\Illuminate\Support\Carbon
    {
        if (! $this->shelf_life_days || $baseDate === null || $baseDate === '') {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($baseDate)->startOfDay()->addDays($this->shelf_life_days);
    }

    /**
     * Ảnh sản phẩm theo thứ tự hiển thị — phần tử đầu tiên là ảnh chính.
     * Thứ tự do SyncProductGalleryAction ghi vào order_column (Spatie tự sort theo cột này).
     *
     * @return Collection<int, array{id: string, url: string, thumb_url: string, is_main: bool}>
     */
    public function galleryImages(): Collection
    {
        $urls = app(MediaUrlService::class);

        return $this->getMedia(self::GALLERY_COLLECTION)
            ->values()
            ->map(fn (Media $media, int $i) => [
                'id'        => $media->id,
                'url'       => $urls->url($media, 'medium') ?: $urls->url($media),
                'thumb_url' => $urls->url($media, 'thumb') ?: $urls->url($media),
                'is_main'   => $i === 0,
            ]);
    }

    /** URL ảnh chính; fallback image_url (ảnh đồng bộ từ Sapo) khi chưa upload ảnh nào. */
    public function mainImageUrl(): ?string
    {
        return $this->galleryImages()->first()['url'] ?? ($this->image_url ?: null);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function partnerProducts(): HasMany
    {
        return $this->hasMany(PartnerProduct::class);
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

    public function hasValidDocument(DocumentMasterType $documentType): bool
    {
        return $this->activeDocuments()
            ->where('document_master_type_id', $documentType->id)
            ->where(fn ($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', now()))
            ->exists();
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class)
            ->using(CustomerProduct::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    public function permissionModule(): string
    {
        return 'product';
    }
}
