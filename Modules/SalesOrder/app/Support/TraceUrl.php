<?php

namespace Modules\SalesOrder\Support;

/**
 * URL công khai mã hóa trong QR trên tem: {trace.public_url}/trace/{trace_code}.
 *
 * Tách domain khỏi APP_URL vì URL đã in lên tem là vĩnh viễn — phải là domain thật người tiêu dùng truy cập được
 * (dev thường để APP_URL=127.0.0.1). Đường dẫn khớp route trace.show (Modules/SalesOrder/routes/web.php).
 */
class TraceUrl
{
    public static function for(string $traceCode): string
    {
        return rtrim((string) config('trace.public_url'), '/') . '/trace/' . $traceCode;
    }
}
