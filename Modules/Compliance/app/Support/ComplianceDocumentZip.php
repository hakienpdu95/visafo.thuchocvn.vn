<?php

namespace Modules\Compliance\Support;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Compliance\Models\ComplianceDocument;
use Spatie\MediaLibrary\Support\MediaStream;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceDocumentZip
{
    private MediaStream $stream;

    private array $folders = [];

    private array $included = [];

    private array $skipped = [];

    public function __construct(private readonly string $zipName)
    {
        $this->stream = MediaStream::create($zipName);
    }

    public static function label(ComplianceDocument $document, bool $withOwner = true): string
    {
        $title = $document->documentType?->name ?? $document->custom_name ?? 'Tài liệu';

        return implode(' - ', array_filter([$title, $document->document_number, $withOwner ? $document->documentable?->name : null]));
    }

    public function add(ComplianceDocument $document, string $label, string $parent = ''): void
    {
        $media = $document->getMedia('attachments_private')
            ->filter(fn (Media $m) => Storage::disk($m->disk)->exists($m->getPathRelativeToRoot()));

        if ($media->isEmpty()) {
            $this->skipped[] = ($parent !== '' ? $parent . ' / ' : '') . $label . ' (không có tệp đính kèm)';
            return;
        }

        $folder = ($parent !== '' ? $this->sanitize($parent) . '/' : '') . $this->sanitize($label);
        $this->folders[$folder] = ($this->folders[$folder] ?? 0) + 1;
        if ($this->folders[$folder] > 1) {
            $folder .= ' (' . $this->folders[$folder] . ')';
        }

        foreach ($media as $m) {
            $m->setCustomProperty('zip_filename_prefix', $folder . '/');
        }

        $this->stream->addMedia($media);
        $this->included[] = $folder . ' — ' . $media->count() . ' tệp';
    }

    public function skip(string $reason): void
    {
        $this->skipped[] = $reason;
    }

    public function isEmpty(): bool
    {
        return $this->included === [];
    }

    public function response(string $heading, string $exportedBy): StreamedResponse
    {
        $summary = $heading . ' — xuất lúc ' . now()->format('d/m/Y H:i') . ' bởi ' . $exportedBy . "\r\n\r\n"
            . 'Đã gom (' . count($this->included) . "):\r\n- " . implode("\r\n- ", $this->included) . "\r\n"
            . ($this->skipped ? "\r\nBỏ qua (" . count($this->skipped) . "):\r\n- " . implode("\r\n- ", $this->skipped) . "\r\n" : '');

        return new StreamedResponse(function () use ($summary) {
            $zip = $this->stream->getZipStream(false);
            $zip->addFile('_danh-sach.txt', "\u{FEFF}" . $summary);
            $zip->finish();
        }, 200, [
            'Content-Disposition' => 'attachment; filename="' . $this->zipName . '"',
            'Content-Type'        => 'application/zip',
        ]);
    }

    private function sanitize(string $name): string
    {
        return Str::limit(trim(preg_replace('#[\\\\/:*?"<>|]+#u', '-', $name)), 120, '');
    }
}
