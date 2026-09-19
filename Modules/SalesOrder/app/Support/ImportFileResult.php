<?php

namespace Modules\SalesOrder\Support;

readonly class ImportFileResult
{
    /**
     * @param  string[]  $newProducts  "SKU (Tên)" của các sản phẩm vừa được tự động tạo mới
     */
    private function __construct(
        public string $fileName,
        public string $status,
        public ?string $misaRefId,
        public ?string $message,
        public int $itemsCount,
        public array $newProducts = [],
    ) {}

    public static function imported(string $fileName, string $misaRefId, int $itemsCount, array $newProducts = []): self
    {
        $message = null;
        if ($newProducts !== []) {
            $message = 'Đã tự động tạo mới ' . count($newProducts)
                . ' sản phẩm chưa có trong danh mục: ' . implode(', ', $newProducts) . '.';
        }

        return new self($fileName, 'imported', $misaRefId, $message, $itemsCount, $newProducts);
    }

    public static function duplicate(string $fileName, string $misaRefId): self
    {
        return new self($fileName, 'duplicate', $misaRefId, "Phiếu \"$misaRefId\" đã tồn tại — bỏ qua.", 0);
    }

    public static function failed(string $fileName, string $message, ?string $misaRefId = null): self
    {
        return new self($fileName, 'failed', $misaRefId, $message, 0);
    }

    public function toArray(): array
    {
        return [
            'file_name' => $this->fileName,
            'status' => $this->status,
            'misa_ref_id' => $this->misaRefId,
            'message' => $this->message,
            'items_count' => $this->itemsCount,
        ];
    }
}
