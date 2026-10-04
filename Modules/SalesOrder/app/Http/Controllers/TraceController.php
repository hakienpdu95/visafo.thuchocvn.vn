<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Queries\GetTraceabilityHandler;
use Modules\SalesOrder\Queries\GetTraceabilityQuery;

/**
 * Trang truy xuất nguồn gốc CÔNG KHAI (không đăng nhập) — người tiêu dùng quét QR trên tem.
 */
class TraceController extends Controller
{
    public function show(string $traceCode, GetTraceabilityHandler $handler)
    {
        $trace = $handler->handle(new GetTraceabilityQuery($traceCode));

        if ($trace === null) {
            return response()->view('traceability.not-found', ['traceCode' => $traceCode], 404);
        }

        return view('traceability.show', ['trace' => $trace]);
    }

    /**
     * Phát file hồ sơ doanh nghiệp (disk private) cho tab "Thương hiệu". Chỉ phục vụ file ảnh/PDF thuộc
     * hồ sơ đang nằm trong whitelist công khai — mọi file khác (kể cả của hồ sơ nội bộ) trả 404.
     */
    public function document(string $traceCode, string $mediaId)
    {
        abort_unless(PrintLog::query()->where('trace_code', $traceCode)->exists(), 404);

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('collection_name', 'attachments_private')
            ->whereIn('mime_type', GetTraceabilityHandler::PUBLIC_DOCUMENT_MIMES)
            ->where('model_type', (new ComplianceDocument())->getMorphClass())
            ->whereIn('model_id', ComplianceDocument::query()->publicCompanyProfile()->select('id'))
            ->firstOrFail();

        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->getPathRelativeToRoot()), 404);

        return $disk->response($media->getPathRelativeToRoot(), $media->file_name, [
            'Content-Type'           => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'public, max-age=3600',
        ], 'inline');
    }
}
