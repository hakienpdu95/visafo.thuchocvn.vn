<?php

namespace Modules\GoodsReceipt\Support;

readonly class ImportBatchSummary
{
    /**
     * @param  ImportFileResult[]  $results
     */
    public function __construct(
        public array $results,
    ) {}

    public function totalFiles(): int
    {
        return count($this->results);
    }

    public function importedCount(): int
    {
        return count(array_filter($this->results, fn (ImportFileResult $r) => $r->status === 'imported'));
    }

    public function duplicateCount(): int
    {
        return count(array_filter($this->results, fn (ImportFileResult $r) => $r->status === 'duplicate'));
    }

    public function failedCount(): int
    {
        return count(array_filter($this->results, fn (ImportFileResult $r) => $r->status === 'failed'));
    }

    public function toArray(): array
    {
        return [
            'total_files' => $this->totalFiles(),
            'imported_count' => $this->importedCount(),
            'duplicate_count' => $this->duplicateCount(),
            'failed_count' => $this->failedCount(),
            'summary_message' => $this->summaryMessage(),
            'results' => array_map(fn (ImportFileResult $r) => $r->toArray(), $this->results),
        ];
    }

    public function summaryMessage(): string
    {
        $message = "Import thành công {$this->importedCount()}/{$this->totalFiles()} file.";

        if ($this->duplicateCount() > 0) {
            $message .= " Bỏ qua {$this->duplicateCount()} file do đã tồn tại.";
        }

        if ($this->failedCount() > 0) {
            $message .= " Lỗi {$this->failedCount()} file.";
        }

        return $message;
    }
}
