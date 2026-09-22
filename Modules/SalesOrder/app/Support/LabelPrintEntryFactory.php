<?php

namespace Modules\SalesOrder\Support;

use Modules\SalesOrder\Models\PrintLog;

/** Dựng "mục in" (một tem = một PrintLog) cho view labels.master_print. */
class LabelPrintEntryFactory
{
    /** QR trên mọi tem trỏ cố định về trang doanh nghiệp trên esupplychain.vn (không còn theo trace_code riêng từng tem). */
    private const QR_TARGET_URL = 'https://esupplychain.vn/doanh-nghiep/3721';

    public static function make(string $viewPath, PrintLog $log): object
    {
        return (object) [
            'viewPath'   => $viewPath,
            'item'       => $log->orderItem,
            'log'        => $log,
            'order'      => $log->orderItem->salesOrder,
            'attributes' => $log->attributes,
            'qrSvg'      => QrSvg::make(self::QR_TARGET_URL),
            // Khổ giấy in thực tế của mẫu đang dùng (VD "100x75") — labels.master_print đọc để set @page.
            // Cùng thứ tự ưu tiên với LabelViewResolver::forLog: mẫu chọn khi in → mẫu gán cho sản phẩm.
            'size'       => $log->labelTemplate?->default_size ?: $log->orderItem?->product?->labelTemplate?->default_size,
        ];
    }
}
