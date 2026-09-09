<?php

namespace Modules\Sapo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Modules\Sapo\Services\SapoClient;
use Modules\Sapo\Support\SapoProductMapper;
use Modules\Sapo\Support\SapoProductSyncRecorder;
use Throwable;

class SyncSapoProductsCommand extends Command
{
    protected $signature = 'sapo:sync-products {--limit=50 : Số sản phẩm mỗi trang gọi về từ Sapo}';

    protected $description = 'Đồng bộ danh mục sản phẩm (Product + Variant) từ Sapo Store về bảng products nội bộ.';

    private const SOURCE = 'polling';

    public function handle(SapoClient $client, SapoProductMapper $mapper, SapoProductSyncRecorder $recorder): int
    {
        if (! config('sapo.base_url') || ! config('sapo.access_token')) {
            $this->error('Chưa cấu hình SAPO_BASE_URL / SAPO_ACCESS_TOKEN trong .env.');

            return self::FAILURE;
        }

        $limit   = max(1, (int) $this->option('limit'));
        $page    = 1;
        $synced  = 0;
        $failed  = 0;

        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% biến thể đã đồng bộ [%bar%] trang hiện tại: ' . '%message%');
        $bar->setMessage((string) $page);
        $bar->start();

        while (true) {
            $bar->setMessage((string) $page);

            try {
                $response = $client->getProducts($page, $limit);
            } catch (ConnectionException $e) {
                $bar->finish();
                $this->newLine(2);
                $this->error('Không kết nối được tới Sapo: ' . $e->getMessage());

                return self::FAILURE;
            }

            if ($response->failed()) {
                $bar->finish();
                $this->newLine(2);
                $this->error("Không gọi được API Sapo (trang {$page}): HTTP {$response->status()}");

                return self::FAILURE;
            }

            $products = $response->json('products', []);

            if (empty($products)) {
                break;
            }

            foreach ($products as $productPayload) {
                foreach ($productPayload['variants'] ?? [] as $variantPayload) {
                    try {
                        $product = $mapper->upsertVariant($productPayload, $variantPayload);
                        $recorder->success(self::SOURCE, 'products/update', $product);
                        $synced++;
                    } catch (Throwable $e) {
                        $failed++;
                        $recorder->failure(self::SOURCE, 'products/update', $productPayload, $variantPayload, $e->getMessage());
                        $this->components->warn("Bỏ qua variant id={$variantPayload['id']}: {$e->getMessage()}");
                    }

                    $bar->advance();
                }
            }

            $page++;

            $this->throttle($response);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Đã đồng bộ {$synced} biến thể sản phẩm từ Sapo về bảng products.");

        if ($failed > 0) {
            $this->warn("{$failed} biến thể bị bỏ qua do lỗi — xem chi tiết ở trên.");
        }

        return self::SUCCESS;
    }

    /** Tránh bị Sapo chặn do gọi API quá nhanh — ưu tiên đọc header rate-limit thực tế nếu có. */
    private function throttle(Response $response): void
    {
        $header = $response->header('X-Sapo-Shop-Api-Call-Limit');

        if ($header && str_contains($header, '/')) {
            [$used, $limit] = array_map('intval', explode('/', $header));

            if ($limit > 0 && ($limit - $used) <= 5) {
                sleep(3);

                return;
            }
        }

        sleep(1);
    }
}
