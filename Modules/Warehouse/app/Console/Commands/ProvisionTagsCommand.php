<?php

namespace Modules\Warehouse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Warehouse\Actions\Backend\ProvisionRetailItemTagsAction;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Support\QrCodeGenerator;
use Spatie\LaravelPdf\Facades\Pdf;

class ProvisionTagsCommand extends Command
{
    protected $signature = 'tags:provision {count : Số lượng tem cần khởi tạo} {--prefix= : Tiền tố chữ cho gs1_serial của cuộn tem này (VD: TH26)}';

    protected $description = 'Khởi tạo một cuộn tem tiền định danh (Pre-serialized Tags) — chưa gắn với sản phẩm/lô nào';

    public function handle(ProvisionRetailItemTagsAction $action, QrCodeGenerator $qrCodeGenerator): int
    {
        $count = (int) $this->argument('count');

        if ($count < 1) {
            $this->error('Số lượng tem phải lớn hơn 0.');

            return self::FAILURE;
        }

        $result = $action->handle($count, (string) $this->option('prefix'));

        $this->info("Đã khởi tạo {$result['count']} tem — prefix \"{$result['prefix']}\" — visual_sequence từ {$result['from']} đến {$result['to']}.");

        $paths = $this->exportRoll($result['from'], $result['to'], $qrCodeGenerator);

        $this->info('File CSV: ' . $paths['csv']);
        $this->info('File PDF: ' . $paths['pdf']);

        return self::SUCCESS;
    }

    /** @return array{csv: string, pdf: string} */
    private function exportRoll(int $from, int $to, QrCodeGenerator $qrCodeGenerator): array
    {
        $tags = RetailItemTag::withoutTenant()
            ->whereBetween('visual_sequence', [$from, $to])
            ->orderBy('visual_sequence')
            ->get(['uid', 'gs1_serial', 'visual_sequence', 'qr_code']);

        $directory = 'tag-rolls';
        Storage::makeDirectory($directory);

        $csvRelativePath = "{$directory}/roll-{$from}-{$to}.csv";
        $this->writeCsv(Storage::path($csvRelativePath), $tags);

        $pdfRelativePath = "{$directory}/roll-{$from}-{$to}.pdf";
        $rows = $tags->map(fn ($tag) => [
            'gs1_serial'      => $tag->gs1_serial,
            'visual_sequence' => $tag->visual_sequence,
            'svg'             => $qrCodeGenerator->toSvg($tag->qr_code, 160),
        ]);

        Pdf::view('warehouse::tag_rolls.print', ['tags' => $rows])
            ->format('a4')
            ->withBrowsershot(fn ($browsershot) => $browsershot->setChromePath(config('warehouse.chrome_path', '/usr/bin/google-chrome'))->noSandbox())
            ->save(Storage::path($pdfRelativePath));

        return ['csv' => Storage::path($csvRelativePath), 'pdf' => Storage::path($pdfRelativePath)];
    }

    private function writeCsv(string $path, \Illuminate\Support\Collection $tags): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['visual_sequence', 'gs1_serial', 'uid', 'qr_code'], ',', '"', '\\');

        foreach ($tags as $tag) {
            fputcsv($handle, [$tag->visual_sequence, $tag->gs1_serial, $tag->uid, $tag->qr_code], ',', '"', '\\');
        }

        fclose($handle);
    }
}
