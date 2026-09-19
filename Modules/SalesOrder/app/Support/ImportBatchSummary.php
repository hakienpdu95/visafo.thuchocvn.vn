<?php

namespace Modules\SalesOrder\Support;

readonly class ImportBatchSummary
{
    /**
     * @param  ImportFileResult[]  $results
     */
    public function __construct(
        public array $results,
    ) {}

    public function importedCount(): int
    {
        return $this->countByStatus('imported');
    }

    public function duplicateCount(): int
    {
        return $this->countByStatus('duplicate');
    }

    public function failedCount(): int
    {
        return $this->countByStatus('failed');
    }

    /** @return string[] */
    public function newProducts(): array
    {
        return array_merge(...array_map(fn (ImportFileResult $r) => $r->newProducts, $this->results));
    }

    public function summaryMessage(): string
    {
        $message = "Import thành công {$this->importedCount()} phiếu.";

        if ($this->duplicateCount() > 0) {
            $message .= " Bỏ qua {$this->duplicateCount()} phiếu trùng lặp.";
        }

        $newProducts = $this->newProducts();
        if ($newProducts !== []) {
            $message .= ' Đã tự động tạo mới ' . count($newProducts)
                . ' sản phẩm chưa có trong danh mục: ' . implode(', ', $newProducts) . '.';
        }

        if ($this->failedCount() > 0) {
            $message .= " Lỗi {$this->failedCount()} file.";
        }

        return $message;
    }

    public function toArray(): array
    {
        return [
            'total_files' => count($this->results),
            'imported_count' => $this->importedCount(),
            'duplicate_count' => $this->duplicateCount(),
            'failed_count' => $this->failedCount(),
            'summary_message' => $this->summaryMessage(),
            'results' => array_map(fn (ImportFileResult $r) => $r->toArray(), $this->results),
        ];
    }

    private function countByStatus(string $status): int
    {
        return count(array_filter($this->results, fn (ImportFileResult $r) => $r->status === $status));
    }
}
