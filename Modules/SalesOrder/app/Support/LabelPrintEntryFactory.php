<?php

namespace Modules\SalesOrder\Support;

use Modules\SalesOrder\Models\PrintLog;

/** Dựng "mục in" (một tem = một PrintLog) cho view labels.master_print. */
class LabelPrintEntryFactory
{
    public static function make(string $viewPath, PrintLog $log): object
    {
        return (object) [
            'viewPath'   => $viewPath,
            'item'       => $log->orderItem,
            'log'        => $log,
            'order'      => $log->orderItem->salesOrder,
            'attributes' => $log->attributes,
            // QR độc nhất cho từng tem: trỏ tới trang truy xuất CÔNG KHAI theo trace_code của chính bản ghi này.
            'qrSvg'      => QrSvg::make(route('trace.show', $log->trace_code)),
        ];
    }
}
