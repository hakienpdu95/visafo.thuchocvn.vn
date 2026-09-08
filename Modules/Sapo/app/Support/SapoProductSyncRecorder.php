<?php

namespace Modules\Sapo\Support;

use Modules\Product\Models\Product;
use Modules\Sapo\Models\SapoProductSyncLog;

/**
 * Ghi 1 dòng "sapo_product_sync_logs" cho mỗi lần đồng bộ 1 variant (thành công/thất bại),
 * dùng chung cho cả luồng webhook tức thời và luồng poll định kỳ — phục vụ Tab "Lịch sử đồng
 * bộ Danh mục Sản phẩm" trên dashboard/sapo-sync-log để admin debug khi thiếu SKU nào đó.
 */
class SapoProductSyncRecorder
{
    public function success(string $source, string $topic, Product $product): void
    {
        SapoProductSyncLog::create([
            'source'          => $source,
            'topic'           => $topic,
            'status'          => 'success',
            'sapo_product_id' => $product->sapo_product_id,
            'sapo_variant_id' => $product->sapo_variant_id,
            'product_id'      => $product->id,
            'product_name'    => $product->name,
            'sku'             => $product->sku,
        ]);
    }

    public function failure(string $source, string $topic, array $product, array $variant, string $message): void
    {
        SapoProductSyncLog::create([
            'source'          => $source,
            'topic'           => $topic,
            'status'          => 'failed',
            'sapo_product_id' => isset($product['id']) ? (string) $product['id'] : null,
            'sapo_variant_id' => isset($variant['id']) ? (string) $variant['id'] : null,
            'product_id'      => null,
            'product_name'    => $product['name'] ?? null,
            'sku'             => $variant['sku'] ?? null,
            'message'         => $message,
        ]);
    }

    public function deleteSuccess(string $source, string $sapoProductId, int $affectedCount): void
    {
        SapoProductSyncLog::create([
            'source'          => $source,
            'topic'           => 'products/delete',
            'status'          => 'success',
            'sapo_product_id' => $sapoProductId,
            'message'         => "Đã xóa mềm {$affectedCount} biến thể liên quan.",
        ]);
    }

    public function deleteFailure(string $source, string $sapoProductId, string $message): void
    {
        SapoProductSyncLog::create([
            'source'          => $source,
            'topic'           => 'products/delete',
            'status'          => 'failed',
            'sapo_product_id' => $sapoProductId,
            'message'         => $message,
        ]);
    }
}
