<?php

namespace Modules\Sapo\Console\Commands;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Modules\Sapo\Actions\ProcessSapoOrderWebhookAction;
use Modules\Sapo\Services\SapoClient;

class ReconcileSapoOrdersCommand extends Command
{
    protected $signature = 'sapo:reconcile-orders';

    protected $description = 'Đối chiếu đơn hàng đã bán trên Sapo với trạng thái tem truy vết trên Laravel — bù các sự kiện webhook bị lỡ.';

    public function handle(SapoClient $client, ProcessSapoOrderWebhookAction $action): int
    {
        if (! config('sapo.base_url') || ! config('sapo.organization_id')) {
            $this->warn('Chưa cấu hình SAPO_BASE_URL/SAPO_ORGANIZATION_ID — bỏ qua đối soát.');

            return self::SUCCESS;
        }

        $organization = Organization::find(config('sapo.organization_id'));

        if (! $organization) {
            $this->error('SAPO_ORGANIZATION_ID không hợp lệ.');

            return self::FAILURE;
        }

        TenantContext::set($organization);

        try {
            $response = $client->get(config('sapo.sold_serials_endpoint'), [
                'updated_at_min' => now()->subDay()->toIso8601String(),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->error('Không kết nối được tới Sapo: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($response->failed()) {
            $this->error('Không gọi được API Sapo: ' . $response->status());

            return self::FAILURE;
        }

        $orders    = $response->json('orders', []);
        $processed = 0;

        foreach ($orders as $orderPayload) {
            $action->handle($orderPayload, json_encode($orderPayload));
            $processed++;
        }

        $this->info("Đã đối soát {$processed} đơn hàng từ Sapo.");

        return self::SUCCESS;
    }
}
