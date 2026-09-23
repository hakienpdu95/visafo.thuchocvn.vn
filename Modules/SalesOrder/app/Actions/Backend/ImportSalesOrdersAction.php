<?php

namespace Modules\SalesOrder\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Support\ImportBatchSummary;

class ImportSalesOrdersAction
{
    use AsAction;

    public function __construct(private readonly ImportSalesOrderFileAction $importFile) {}

    public function handle(array $files, ?string $importedById, ?string $deliveryDate = null): ImportBatchSummary
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->importFile->handle($file, $importedById, $deliveryDate);
        }

        return new ImportBatchSummary($results);
    }
}
