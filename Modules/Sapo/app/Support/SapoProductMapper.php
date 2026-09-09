<?php

namespace Modules\Sapo\Support;

use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;

/**
 * Ánh xạ 1 Product + 1 Variant của Sapo thành 1 dòng bảng `products` nội bộ.
 * Dùng chung cho cả luồng poll định kỳ (SyncSapoProductsCommand) lẫn luồng webhook
 * tức thời (ProcessSapoProductWebhookJob) — tránh trùng lặp logic mapping.
 */
class SapoProductMapper
{
    public function upsertVariant(array $product, array $variant): Product
    {
        // Sapo trả "Default Title" cho variant duy nhất của sản phẩm không có phân loại
        // (giống Shopify) — không nối vào tên, tránh ra "Tên SP - Default Title".
        $title = trim((string) ($variant['title'] ?? ''));
        $name  = ($title !== '' && $title !== 'Default Title')
            ? $product['name'] . ' - ' . $title
            : $product['name'];

        $attributes = [
            'sapo_product_id' => (string) $product['id'],
            'name'            => $name,
            'sku'             => ($variant['sku'] ?? null) ?: ('SAPO-' . $variant['id']),
            'barcode'         => $variant['barcode'] ?? null,
            'image_url'       => $product['image']['src'] ?? null,
        ];

        // updateOrCreate không phù hợp ở đây: cột category_id/product_type/unit là NOT NULL
        // không có default, nhưng Sapo không trả về các trường này — chỉ nên gán giá trị mặc
        // định lúc tạo mới, tránh ghi đè giá trị đã được người dùng chỉnh tay sau đó.
        $item = Product::where('sapo_variant_id', (string) $variant['id'])->first();

        if ($item) {
            $item->update($attributes);

            return $item;
        }

        return Product::create($attributes + [
            'sapo_variant_id' => (string) $variant['id'],
            // Sapo không trả về nhóm thực phẩm/bản chất hàng — mặc định hàng bao gói sẵn
            // mua về bán lại qua POS, người dùng chỉnh tay sau nếu cần.
            'category_id'     => Category::where('code', 'prepackaged_food')->value('id'),
            'product_type'    => ProductType::TradingGood->value,
            'unit'            => 'Cái',
            'status'          => 'active',
        ]);
    }

    /** Xóa mềm toàn bộ variant thuộc 1 sapo_product_id (topic products/delete). */
    public function deleteBySapoProductId(string $sapoProductId): int
    {
        return Product::where('sapo_product_id', $sapoProductId)->delete();
    }
}
