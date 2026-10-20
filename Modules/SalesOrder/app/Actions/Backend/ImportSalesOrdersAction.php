<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Support\ImportBatchSummary;
use Modules\SalesOrder\Support\ImportFileResult;
use Modules\SalesOrder\Support\UploadLimits;

class ImportSalesOrdersAction
{
    use AsAction;

    private const EXTENSIONS = ['xls', 'xlsx'];

    public function __construct(private readonly ImportSalesOrderFileAction $importFile) {}

    /**
     * @param  UploadedFile[]  $files
     * @param  string[]  $expectedNames
     */
    public function handle(array $files, ?string $importedById, ?string $deliveryDate = null, array $expectedNames = []): ImportBatchSummary
    {
        $results = [];
        $received = [];

        foreach ($files as $file) {
            $name = $file->getClientOriginalName();
            $received[] = $name;
            $results[] = $this->reject($file) ?? $this->importFile->handle($file, $importedById, $deliveryDate);
        }

        foreach ($this->missing($expectedNames, $received) as $name) {
            $results[] = ImportFileResult::failed($name, 'Máy chủ không nhận được file (giới hạn tối đa '
                . UploadLimits::maxFilesPerRequest() . ' file mỗi lần tải lên). Vui lòng tải lại file này.');
        }

        return new ImportBatchSummary($results);
    }

    private function reject(UploadedFile $file): ?ImportFileResult
    {
        $name = $file->getClientOriginalName();

        if (! $file->isValid()) {
            return ImportFileResult::failed($name, match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File vượt quá dung lượng cho phép của máy chủ ('
                    . round(UploadLimits::maxFileBytes() / 1024 / 1024, 1) . ' MB).',
                UPLOAD_ERR_PARTIAL => 'File tải lên bị gián đoạn. Vui lòng tải lại.',
                default => 'Tải file lên thất bại: ' . $file->getErrorMessage(),
            });
        }

        if (! in_array(strtolower($file->getClientOriginalExtension()), self::EXTENSIONS, true)) {
            return ImportFileResult::failed($name, 'Sai định dạng — chỉ chấp nhận file Excel .xls, .xlsx.');
        }

        if ($file->getSize() > UploadLimits::IMPORT_MAX_FILE_BYTES) {
            return ImportFileResult::failed($name, 'File vượt quá 10 MB.');
        }

        return null;
    }

    /** @return string[] */
    private function missing(array $expected, array $received): array
    {
        $left = array_count_values($received);
        $missing = [];

        foreach ($expected as $name) {
            if (($left[$name] ?? 0) > 0) {
                $left[$name]--;
            } else {
                $missing[] = $name;
            }
        }

        return $missing;
    }
}
