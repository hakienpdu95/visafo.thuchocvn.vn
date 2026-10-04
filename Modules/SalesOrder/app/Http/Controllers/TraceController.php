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
    /** Ảnh dựng sẵn cho thẻ hồ sơ: thumb (lưới) / preview (lightbox) — chiều rộng px. PDF render trang 1. */
    private const DOCUMENT_VARIANTS = ['thumb' => 480, 'preview' => 1400];

    public function document(string $traceCode, string $mediaId, ?string $variant = null)
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

        if ($variant !== null) {
            return $this->documentImage($media, $variant);
        }

        return $disk->response($media->getPathRelativeToRoot(), $media->file_name, [
            'Content-Type'           => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'public, max-age=3600',
        ], 'inline');
    }

    /**
     * JPEG thu nhỏ của file hồ sơ (ảnh scan hoặc trang 1 của PDF) — sinh 1 lần rồi cache trên disk local
     * (media bất biến: upload lại = media mới). Lỗi render (PDF hỏng/mã hóa) → 404, view tự rơi về icon.
     */
    private function documentImage(Media $media, string $variant)
    {
        $cache = Storage::disk('local');
        $cachePath = 'trace-document-thumbs/' . $media->id . '-' . $variant . '.jpg';

        if (! $cache->exists($cachePath)) {
            try {
                $source = Storage::disk($media->disk)->path($media->getPathRelativeToRoot());
                $image = new \Imagick();
                if ($media->mime_type === 'application/pdf') {
                    $image->setResolution(150, 150);
                    $source .= '[0]';
                }
                $image->readImage($source);
                $image->setImageBackgroundColor('white');
                $image = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN); // nền trong suốt → trắng
                $image->autoOrient();
                if ($image->getImageWidth() > self::DOCUMENT_VARIANTS[$variant]) {
                    $image->thumbnailImage(self::DOCUMENT_VARIANTS[$variant], 0);
                }
                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(82);
                $image->stripImage();
                $cache->put($cachePath, $image->getImageBlob());
                $image->clear();
            } catch (\Throwable $e) {
                // Server thiếu Imagick/Ghostscript hoặc policy ImageMagick chặn PDF: ảnh scan vẫn hiện được bằng
                // file gốc; PDF → 404 để view rơi về icon PDF (bấm vẫn mở được bản PDF đầy đủ).
                report($e);
                abort_if($media->mime_type === 'application/pdf', 404);

                return Storage::disk($media->disk)->response($media->getPathRelativeToRoot(), $media->file_name, [
                    'Content-Type'           => $media->mime_type,
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control'          => 'public, max-age=3600',
                ], 'inline');
            }
        }

        return $cache->response($cachePath, null, [
            'Content-Type'           => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'public, max-age=86400',
        ], 'inline');
    }
}
