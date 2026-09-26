<?php

namespace Modules\LabelTemplate\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\Product;

class LabelTemplate extends TenantAwareModel
{
    use HasCreator;

    /**
     * Đường dẫn view hợp lệ: bắt đầu bằng "labels." rồi các đoạn [a-zA-Z0-9_-] cách nhau bởi dấu chấm.
     * Ép tiền tố "labels." để mẫu tem không thể trỏ tới view hệ thống khác (layout, trang lỗi...).
     */
    public const VIEW_PATH_REGEX = '/^labels(\.[A-Za-z0-9_-]+)+$/';

    protected $fillable = [
        'name',
        'view_path',
        'description',
        'default_size',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'label_template_id');
    }

    public function permissionModule(): string
    {
        return 'label_template';
    }
}
