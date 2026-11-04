<?php

namespace Modules\HanoiCheckOrder\Console\Commands;

use Illuminate\Console\Command;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Enums\OrderSyncStatus;
use Modules\HanoiCheckOrder\Jobs\SyncHanoiCheckOrderJob;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;

class RefreshHanoiCheckOrdersCommand extends Command
{
    protected $signature = 'hanoicheck-orders:refresh {--all : Gồm cả đơn cũ hơn HANOICHECK_ORDER_REFRESH_DAYS}';

    protected $description = 'Đọc lại trạng thái và đồng bộ các đơn đã liên kết HanoiCheck chưa chốt (chạy định kỳ)';

    public function handle(): int
    {
        if (! HanoiCheckClient::enabled() || HanoiCheckClient::fake()) {
            $this->warn('HanoiCheck chưa bật (HANOICHECK_ENABLED=false hoặc HANOICHECK_FAKE=true) — bỏ qua.');

            return self::SUCCESS;
        }

        $days = (int) config('services.hanoicheck.order_refresh_days', 14);
        $count = 0;

        HanoiCheckOrderLink::query()
            ->where('sync_status', '!=', OrderSyncStatus::Locked->value)
            ->when(! $this->option('all'), fn ($q) => $q->where('linked_at', '>=', now()->subDays($days)))
            ->select('id')
            ->chunkById(200, function ($links) use (&$count) {
                foreach ($links as $link) {
                    SyncHanoiCheckOrderJob::dispatch($link->id);
                    $count++;
                }
            });

        $this->info("Đã xếp hàng đồng bộ {$count} đơn.");

        return self::SUCCESS;
    }
}
