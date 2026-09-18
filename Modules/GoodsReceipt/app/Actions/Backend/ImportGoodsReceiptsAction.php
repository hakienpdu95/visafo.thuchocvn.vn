<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Support\ImportBatchSummary;

class ImportGoodsReceiptsAction
{
    use AsAction;

    public function __construct(private readonly ImportGoodsReceiptFileAction $importFile) {}

    public function handle(array $files, ?string $importedById): ImportBatchSummary
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->importFile->handle($file, $importedById);
        }

        return new ImportBatchSummary($results);
    }
}
