<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductCategoryClassifier;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportMasterProductsCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'app:import-master-products
        {--file=spec/Vat_tu__hang_hoa__dich_vu.xlsx : Đường dẫn file Excel (tương đối từ project root hoặc tuyệt đối)}
        {--dry-run : Chỉ báo cáo, không ghi DB}
        {--force : Bỏ qua bước xác nhận}';

    protected $description = 'Import danh mục Vật tư/Hàng hóa/Dịch vụ (Excel) vào bảng products — ánh xạ "Kho ngầm định" sang product_type + category';

    private const KHO_MAP = [
        'KTPTS'  => ['type' => ProductType::RawMaterial, 'category' => 'fresh_meat_seafood'],
        'KTPĐL'  => ['type' => ProductType::RawMaterial, 'category' => 'fresh_meat_seafood'],
        'KRCQ'   => ['type' => ProductType::RawMaterial, 'category' => 'fresh_produce'],
        'KRCQSC' => ['type' => ProductType::RawMaterial, 'category' => 'fresh_produce'],
        'KHLRT'  => ['type' => ProductType::RawMaterial, 'category' => 'fresh_produce'],
        'KHQ'    => ['type' => ProductType::TradingGood, 'category' => 'fresh_produce'],
        'KĐK'    => ['type' => ProductType::RawMaterial, 'category' => 'processed_food'],
        'CCDC'   => ['type' => ProductType::Consumables, 'category' => null],
    ];

    public function handle(ProductCategoryClassifier $classifier): int
    {
        $path = $this->option('file');
        $fullPath = str_starts_with($path, '/') ? $path : base_path($path);

        if (!is_file($fullPath)) {
            $this->error("File không tồn tại: $fullPath");
            return self::FAILURE;
        }

        $categoryIds = Category::query()->pluck('id', 'code');
        foreach (self::KHO_MAP as $kho => $rule) {
            if ($rule['category'] !== null && !$categoryIds->has($rule['category'])) {
                $this->error("Category '{$rule['category']}' (dùng cho kho '$kho') chưa tồn tại — chạy CategorySeeder trước.");
                return self::FAILURE;
            }
        }
        if (!$categoryIds->has(ProductCategoryClassifier::PROCESSED)) {
            $this->error("Category '" . ProductCategoryClassifier::PROCESSED . "' chưa tồn tại — chạy CategorySeeder trước.");
            return self::FAILURE;
        }

        $rows = (new FastExcel())->headerRow(2)->import($fullPath);

        if (!$this->option('dry-run') && !$this->confirmToProceed('Import đầy đủ sẽ GHI ĐÈ tên, ĐVT, nhóm hàng, loại hàng của mọi SKU có trong file và tạo mới SKU chưa có.')) {
            return self::FAILURE;
        }

        $imported = 0;
        $skipped  = [];

        $dryRun = (bool) $this->option('dry-run');

        $this->withProgressBar($rows, function (array $row) use ($categoryIds, $classifier, $dryRun, &$imported, &$skipped): void {
            $sku  = trim((string) ($row['Mã'] ?? ''));
            $name = trim((string) ($row['Tên'] ?? ''));
            $unit = trim((string) ($row['ĐVT chính'] ?? ''));
            $kho  = trim((string) ($row['Kho ngầm định'] ?? ''));

            if ($sku === '') {
                $skipped['Thiếu Mã'] = ($skipped['Thiếu Mã'] ?? 0) + 1;
                return;
            }
            if ($name === '' || str_contains($name, 'Chưa có mã')) {
                $skipped['Thiếu Tên / Tên là "Chưa có mã"'] = ($skipped['Thiếu Tên / Tên là "Chưa có mã"'] ?? 0) + 1;
                return;
            }
            if ($unit === '') {
                $skipped['Thiếu ĐVT chính'] = ($skipped['Thiếu ĐVT chính'] ?? 0) + 1;
                return;
            }
            if ($kho === '' || !isset(self::KHO_MAP[$kho])) {
                $label = $kho === '' ? 'Thiếu Kho ngầm định' : "Kho ngầm định không xác định ($kho)";
                $skipped[$label] = ($skipped[$label] ?? 0) + 1;
                return;
            }

            $rule       = self::KHO_MAP[$kho];
            $category   = $rule['category'] !== null && $classifier->isProcessed($name) ? ProductCategoryClassifier::PROCESSED : $rule['category'];
            $categoryId = $category !== null ? $categoryIds->get($category) : null;

            if (!$dryRun) {
                Product::query()->updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name'         => $name,
                        'unit'         => $unit,
                        'category_id'  => $categoryId,
                        'product_type' => $rule['type']->value,
                    ],
                );
            }

            $imported++;
        });

        $this->newLine(2);
        $this->info("Đã import: $imported sản phẩm" . ($dryRun ? ' (dry-run — chưa ghi DB)' : '.'));

        $totalSkipped = array_sum($skipped);
        $this->line("Đã bỏ qua: $totalSkipped dòng");
        foreach ($skipped as $reason => $count) {
            $this->line("  - $reason: $count");
        }

        return self::SUCCESS;
    }
}
