<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\InternalFacility;
use Modules\Product\Enums\DocumentGroupType;
use Modules\SalesOrder\Queries\GetTraceabilityHandler;

/**
 * Chẩn đoán khối "Hồ sơ doanh nghiệp" (tab Thương hiệu, /trace/{code}) trên một môi trường:
 * môi trường render ảnh (Imagick/Ghostscript/policy PDF, quyền ghi cache) và từng hồ sơ hiện/ẩn vì sao.
 */
class TraceDiagnoseDocumentsCommand extends Command
{
    protected $signature = 'trace:diagnose-documents';

    protected $description = 'Kiểm tra vì sao hồ sơ doanh nghiệp hiện/ẩn và ảnh thu nhỏ có render được trên trang truy xuất';

    /** PDF 1 trang tối giản để thử đọc qua Imagick (Ghostscript + policy). */
    private const SAMPLE_PDF = "%PDF-1.1\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

    public function handle(): int
    {
        $this->info('1. Môi trường render ảnh thu nhỏ');
        $this->line('  Imagick extension : ' . (extension_loaded('imagick') ? 'OK' : '<error>THIẾU</error> → ảnh scan dùng file gốc, PDF chỉ hiện icon'));
        $gs = trim((string) @shell_exec('command -v gs 2>/dev/null'));
        $this->line('  Ghostscript (gs)  : ' . ($gs !== '' ? 'OK (' . $gs . ')' : '<error>THIẾU</error> → không render được PDF'));
        $this->line('  Đọc PDF qua Imagick: ' . $this->probePdf());

        $cache = Storage::disk('local');
        try {
            $cache->put('trace-document-thumbs/.probe', 'ok');
            $cache->delete('trace-document-thumbs/.probe');
            $this->line('  Ghi cache thumbs  : OK (' . $cache->path('trace-document-thumbs') . ')');
        } catch (\Throwable $e) {
            $this->line('  Ghi cache thumbs  : <error>LỖI</error> ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('2. Hồ sơ của doanh nghiệp (Trụ sở chính + cơ sở nội bộ)');

        $docs = ComplianceDocument::query()
            ->where('documentable_type', (new InternalFacility())->getMorphClass())
            ->with(['documentType', 'media'])
            ->orderBy('issue_date')
            ->get();

        if ($docs->isEmpty()) {
            $this->warn('  Chưa có hồ sơ nào — tải lên tại dashboard/internal-compliance.');

            return self::SUCCESS;
        }

        $publicIds = ComplianceDocument::query()->publicCompanyProfile()->pluck('id')->all();

        $this->table(['Hồ sơ', 'Loại (code)', 'Nhóm', 'Tệp ảnh/PDF', 'Kết quả'], $docs->map(function (ComplianceDocument $doc) use ($publicIds) {
            $type = $doc->documentType;
            $files = $doc->getMedia('attachments_private');
            $shown = $files->filter(fn (Media $m) => in_array($m->mime_type, GetTraceabilityHandler::PUBLIC_DOCUMENT_MIMES, true));

            $reasons = array_filter([
                $type?->document_group !== DocumentGroupType::LegalFacility ? 'không thuộc nhóm Pháp lý cơ sở' : null,
                $type && ! $type->is_public ? 'loại giấy tờ chưa bật "Công khai trên trang truy xuất"' : null,
                $doc->status !== ComplianceDocumentStatus::Active ? 'trạng thái ' . $doc->status->label() : null,
                $doc->isExpired() ? 'đã hết hạn ' . $doc->expiration_date->format('d/m/Y') : null,
            ]);

            return [
                $doc->custom_name ?: ($type?->name ?? '—'),
                $type?->code ?? '—',
                $type?->document_group?->label() ?? '—',
                $shown->count() . '/' . $files->count() . ($files->count() > $shown->count() ? ' (bỏ qua: ' . $files->diff($shown)->pluck('mime_type')->unique()->implode(', ') . ')' : ''),
                in_array($doc->id, $publicIds, true) ? 'HIỆN' : 'ẨN — ' . implode('; ', $reasons),
            ];
        })->all());

        return self::SUCCESS;
    }

    private function probePdf(): string
    {
        if (! extension_loaded('imagick')) {
            return '<comment>bỏ qua (thiếu Imagick)</comment>';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'trace-pdf-') . '.pdf';
        file_put_contents($tmp, self::SAMPLE_PDF);

        try {
            $image = new \Imagick();
            $image->readImage($tmp . '[0]');

            return 'OK';
        } catch (\Throwable $e) {
            return '<error>LỖI</error> ' . $e->getMessage() . ' (kiểm tra policy.xml của ImageMagick: rights="read" pattern="PDF")';
        } finally {
            @unlink($tmp);
        }
    }
}
