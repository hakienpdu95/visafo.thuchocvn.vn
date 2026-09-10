<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;

class ProductMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('datafiles/products_master_data.json');

        if (!File::exists($path)) {
            $this->command?->error("File không tồn tại: $path");
            return;
        }

        $rows = json_decode(File::get($path), true);
        if (!is_array($rows)) {
            $this->command?->error('File JSON không hợp lệ.');
            return;
        }

        $categoryIds = Category::query()->pluck('id', 'code');

        $imported = 0;
        $skipped  = [];

        foreach ($rows as $row) {
            $sku  = trim((string) ($row['sku'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $unit = trim((string) ($row['unit'] ?? ''));
            $type = trim((string) ($row['product_type'] ?? ''));
            $categoryCode = $row['category_code'] ?? null;

            if ($sku === '' || $name === '' || $unit === '' || $type === '') {
                $skipped['Thiếu trường bắt buộc (sku/name/unit/product_type)'] =
                    ($skipped['Thiếu trường bắt buộc (sku/name/unit/product_type)'] ?? 0) + 1;
                continue;
            }

            if (!in_array($type, array_column(ProductType::cases(), 'value'), true)) {
                $skipped["product_type không hợp lệ ($type)"] = ($skipped["product_type không hợp lệ ($type)"] ?? 0) + 1;
                continue;
            }

            $categoryId = null;
            if ($categoryCode !== null) {
                if (!$categoryIds->has($categoryCode)) {
                    $skipped["category_code không xác định ($categoryCode)"] = ($skipped["category_code không xác định ($categoryCode)"] ?? 0) + 1;
                    continue;
                }
                $categoryId = $categoryIds->get($categoryCode);
            }

            Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name'         => $name,
                    'unit'         => $unit,
                    'product_type' => $type,
                    'category_id'  => $categoryId,
                ],
            );

            $imported++;
        }

        $this->command?->info("  ✓ products_master_data.json: $imported sản phẩm đã import.");
        foreach ($skipped as $reason => $count) {
            $this->command?->warn("  ⚠ Bỏ qua ($count): $reason");
        }
    }
}
