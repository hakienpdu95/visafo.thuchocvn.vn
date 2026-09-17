<?php

namespace Modules\SalesPackage\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\SalesPackage\Models\SalesPackage;
use Spatie\LaravelPdf\Facades\Pdf;
use ZipArchive;

class SalesPackageExportService
{
    public function export(SalesPackage $package): string
    {
        $package->load(['customer', 'items.document.documentType', 'items.media']);

        $coverPdf = Pdf::view('salespackage::pdf.cover-sheet', ['package' => $package])->generatePdfContent();

        $zipPath = tempnam(sys_get_temp_dir(), 'sales_package_') . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('Muc_Luc_Goi_Thau.pdf', $coverPdf);

        foreach ($package->items as $item) {
            $mediaItems = $item->is_custom
                ? $item->getMedia('custom_document')
                : ($item->document?->getMedia('attachments_private') ?? collect());

            $folder = Str::slug($item->groupLabel());

            foreach ($mediaItems as $index => $media) {
                $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
                if ($contents === null) {
                    continue;
                }

                $suffix    = $mediaItems->count() > 1 ? '-' . ($index + 1) : '';
                $entryName = $folder . '/' . Str::slug($item->displayName()) . '-' . $item->id . $suffix . '.' . $media->extension;
                $zip->addFromString($entryName, $contents);
            }
        }

        $zip->close();

        return $zipPath;
    }

    public function downloadName(SalesPackage $package): string
    {
        return 'goi-chao-hang-' . Str::slug($package->customer->name) . '-v' . $package->version . '.zip';
    }
}
