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
            $media = $item->is_custom
                ? $item->getFirstMedia('custom_document')
                : $item->document?->getFirstMedia('attachments_private');

            if (! $media) {
                continue;
            }

            $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
            if ($contents === null) {
                continue;
            }

            $folder     = Str::slug($item->groupLabel());
            $entryName  = $folder . '/' . Str::slug($item->displayName()) . '-' . $item->id . '.' . $media->extension;
            $zip->addFromString($entryName, $contents);
        }

        $zip->close();

        return $zipPath;
    }

    public function downloadName(SalesPackage $package): string
    {
        return 'goi-chao-hang-' . Str::slug($package->customer->name) . '-v' . $package->version . '.zip';
    }
}
