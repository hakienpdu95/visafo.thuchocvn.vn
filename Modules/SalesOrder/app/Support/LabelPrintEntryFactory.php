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
            // QR động: mỗi tem trỏ về trang truy xuất của chính trace_code của nó
            'qrSvg'      => QrSvg::make(TraceUrl::for($log->trace_code)),
            'size'       => $log->labelTemplate ? $log->labelTemplate->default_size : LabelViewResolver::DEFAULT_SIZE,
        ];
    }
}
