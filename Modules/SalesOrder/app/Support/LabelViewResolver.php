<?php

namespace Modules\SalesOrder\Support;

use Modules\Product\Models\Product;
use Modules\SalesOrder\Models\PrintLog;

/**
 * Xác định Blade view của tem in: mẫu tem gán cho sản phẩm (Master Data),
 * nếu chưa gán thì dùng mẫu mặc định của hệ thống.
 */
class LabelViewResolver
{
    public const DEFAULT_VIEW = 'labels.templates.general_default_60x40';

    /**
     * Thứ tự ưu tiên: mẫu đã chọn khi in (lưu trên log) → mẫu gán cho sản phẩm → mẫu mặc định.
     * Log lưu mẫu đã chọn nên In lại luôn ra đúng giao diện lúc in.
     */
    public function forLog(PrintLog $log): string
    {
        return $log->labelTemplate?->view_path ?: $this->forProduct($log->orderItem?->product);
    }

    public function forProduct(?Product $product): string
    {
        return $product?->labelTemplate?->view_path ?: self::DEFAULT_VIEW;
    }
}
