<?php

namespace Modules\Sapo\Jobs;

use App\Foundation\Jobs\TenantAwareJob;
use Illuminate\Support\Facades\Log;
use Modules\Sapo\Support\SapoProductMapper;
use Modules\Sapo\Support\SapoProductSyncRecorder;
use Throwable;

/**
 * Xử lý ngầm payload webhook products/create|update|delete từ Sapo — controller chỉ
 * dispatch job này và trả 200 OK ngay, không mapping data trực tiếp trong request cycle
 * (tránh Sapo đánh dấu timeout nếu xử lý chậm).
 */
class ProcessSapoProductWebhookJob extends TenantAwareJob
{
    private const SOURCE = 'webhook';

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly string $topic,
        public readonly array $payload,
    ) {
        parent::__construct();
    }

    public function handle(SapoProductMapper $mapper, SapoProductSyncRecorder $recorder): void
    {
        $this->withTenant(function () use ($mapper, $recorder) {
            match ($this->topic) {
                'products/create', 'products/update' => $this->syncProduct($mapper, $recorder),
                'products/delete' => $this->deleteProduct($mapper, $recorder),
                default => Log::warning("Sapo webhook: topic không xác định [{$this->topic}]"),
            };
        });
    }

    private function syncProduct(SapoProductMapper $mapper, SapoProductSyncRecorder $recorder): void
    {
        $variants = $this->payload['variants'] ?? [];

        if (empty($variants)) {
            Log::warning("Sapo webhook [{$this->topic}]: sản phẩm id=" . ($this->payload['id'] ?? '?') . ' không có variants, bỏ qua.');

            return;
        }

        foreach ($variants as $variant) {
            try {
                $product = $mapper->upsertVariant($this->payload, $variant);
                $recorder->success(self::SOURCE, $this->topic, $product);
            } catch (Throwable $e) {
                Log::error("Sapo webhook [{$this->topic}] xử lý variant thất bại: {$e->getMessage()}", [
                    'sapo_product_id' => $this->payload['id'] ?? null,
                    'sapo_variant_id' => $variant['id'] ?? null,
                ]);
                $recorder->failure(self::SOURCE, $this->topic, $this->payload, $variant, $e->getMessage());
            }
        }
    }

    private function deleteProduct(SapoProductMapper $mapper, SapoProductSyncRecorder $recorder): void
    {
        $sapoProductId = (string) ($this->payload['id'] ?? '');

        try {
            $deleted = $mapper->deleteBySapoProductId($sapoProductId);
            $recorder->deleteSuccess(self::SOURCE, $sapoProductId, $deleted);
        } catch (Throwable $e) {
            Log::error("Sapo webhook [products/delete] thất bại: {$e->getMessage()}", ['sapo_product_id' => $sapoProductId]);
            $recorder->deleteFailure(self::SOURCE, $sapoProductId, $e->getMessage());
        }
    }
}
