<?php

namespace Modules\HanoiCheckOrder\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\HanoiCheck\Actions\Backend\CallHanoiCheckApiAction;
use Modules\HanoiCheck\Actions\Backend\SyncHanoiCheckRecordAction;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Enums\SyncStatus;
use Modules\HanoiCheck\Exceptions\HanoiCheckRequestException;
use Modules\HanoiCheck\Queries\GetHanoiCheckSyncStateHandler;
use Modules\HanoiCheck\Queries\GetHanoiCheckSyncStateQuery;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheck\Support\HanoiCheckPayloadBuilder;
use Modules\HanoiCheckOrder\Enums\OrderSyncStatus;
use Modules\HanoiCheckOrder\Enums\RemoteOrderStatus;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\HanoiCheckOrder\Queries\GetRemoteHanoiCheckOrderHandler;
use Modules\HanoiCheckOrder\Queries\GetRemoteHanoiCheckOrderQuery;
use Modules\HanoiCheckOrder\Support\OrderAllocationBuilder;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;

class SyncHanoiCheckOrderAction
{
    use AsAction;

    public function __construct(
        private readonly GetRemoteHanoiCheckOrderHandler $remote,
        private readonly OrderAllocationBuilder $allocator,
        private readonly SyncHanoiCheckRecordAction $records,
        private readonly SyncHanoiCheckTransporterAction $transporters,
        private readonly CallHanoiCheckApiAction $call,
        private readonly GetHanoiCheckSyncStateHandler $state,
        private readonly HanoiCheckClient $client,
    ) {}

    public function handle(HanoiCheckOrderLink $link, int $attempt = 1): OrderSyncStatus
    {
        if (HanoiCheckClient::fake()) {
            return $this->finish($link, OrderSyncStatus::Idle, 'Đang ở chế độ FAKE (HANOICHECK_FAKE=true): chưa gọi API đơn hàng của Sở.');
        }

        $order = $link->salesOrder;
        if ($order === null || $order->trashed()) {
            return $this->finish($link, OrderSyncStatus::Incomplete, 'Đơn VISAFO đã bị xóa — gỡ liên kết hoặc liên kết sang đơn khác.');
        }

        try {
            return $this->run($link, $order, $attempt);
        } catch (HanoiCheckRequestException $e) {
            $message = $e->httpStatus === 404
                ? "Không tìm thấy đơn {$link->hanoicheck_order_code} trên HanoiCheck (sai mã hoặc đơn không thuộc VISAFO)."
                : $e->getMessage();
            $this->finish($link, OrderSyncStatus::Failed, $message);

            if ($e->retryable) {
                throw $e;
            }

            return OrderSyncStatus::Failed;
        }
    }

    public static function targetFor(SalesOrder $order): ?RemoteOrderStatus
    {
        if ($order->status === SalesOrder::STATUS_DELIVERED || $order->delivered_at !== null) {
            return RemoteOrderStatus::DaGiao;
        }
        if ($order->status === SalesOrder::STATUS_SHIPPING || $order->shipped_at !== null) {
            return RemoteOrderStatus::DangGiao;
        }

        $printed = PrintLog::query()
            ->whereIn('order_item_id', $order->items()->select('id'))
            ->where('status', PrintLogStatus::Active->value)
            ->exists();

        return $printed ? RemoteOrderStatus::DangChuanBi : null;
    }

    private function run(HanoiCheckOrderLink $link, SalesOrder $order, int $attempt): OrderSyncStatus
    {
        $remote = $this->remote->handle(new GetRemoteHanoiCheckOrderQuery($link->hanoicheck_order_code));
        $current = RemoteOrderStatus::tryFrom((string) $remote['status']);
        $link->forceFill([
            'remote_status' => $current,
            'school_name'   => $remote['school_name'] ?? $link->school_name,
            'fetched_at'    => now(),
        ]);

        if ($current?->final()) {
            return $this->finish($link, OrderSyncStatus::Locked, "Đơn đang ở trạng thái \"{$current->label()}\" trên HanoiCheck — không còn cập nhật được.");
        }

        $allocation = $this->allocator->build($order, $remote['lines']);
        $link->snapshot = ['lines' => $allocation['lines'], 'leftover' => $allocation['leftover'], 'status_options' => $remote['status_options']];

        $deps = $this->ensureDependencies($link, $allocation['batch_ids'], $attempt);
        if ($deps['waiting'] !== null) {
            return $this->finish($link, OrderSyncStatus::Waiting, $deps['waiting']);
        }

        $problems = array_merge($deps['problems'], collect($allocation['lines'])->pluck('error')->filter()->values()->all());
        if ($remote['lines'] === []) {
            $problems[] = 'Đơn trên HanoiCheck chưa có dòng hàng nào.';
        }

        $this->pushProcess($link, $allocation['lines'], $attempt);

        $target = self::targetFor($order);
        $problems = array_merge($problems, $this->advanceStatus($link, $current, $target, $allocation['complete'], $attempt));

        return $problems === []
            ? $this->finish($link, OrderSyncStatus::Synced, $target ? "Đã cập nhật lên HanoiCheck: {$link->remote_status?->label()}." : 'Đã khai nguồn hàng lên HanoiCheck.')
            : $this->finish($link, OrderSyncStatus::Incomplete, implode(' | ', array_unique($problems)));
    }

    private function ensureDependencies(HanoiCheckOrderLink $link, array $batchIds, int $attempt): array
    {
        $settle = (int) config('services.hanoicheck.settle_seconds', 5);
        $environment = SyncHanoiCheckTransporterAction::environment();
        $recent = fn ($state) => $state['last']?->status === SyncStatus::Accepted && $state['last']->created_at->gt(now()->subSeconds($settle));
        $problems = [];

        foreach ($batchIds as $batchId) {
            $state = $this->state->handle(new GetHanoiCheckSyncStateQuery(SyncEntity::ProductBatch, (string) $batchId, $environment));
            if ($state['synced']) {
                if ($recent($state)) {
                    return ['waiting' => 'Đang chờ HanoiCheck ghi xong lô vừa đồng bộ.', 'problems' => []];
                }

                continue;
            }

            $status = $this->records->handle(SyncEntity::ProductBatch, (string) $batchId, $attempt);
            if ($status === SyncStatus::Pending || $status === SyncStatus::Accepted) {
                return ['waiting' => 'Đang đồng bộ lô / thực phẩm / NCC mà đơn tham chiếu lên HanoiCheck.', 'problems' => []];
            }
            if ($status === SyncStatus::Invalid) {
                $reason = $this->state->handle(new GetHanoiCheckSyncStateQuery(SyncEntity::ProductBatch, (string) $batchId, $environment))['last']?->error_message;
                $problems[] = 'Lô chưa gửi được lên HanoiCheck: ' . ($reason ?: 'thiếu dữ liệu bắt buộc.');
            }
        }

        if ($link->transporter !== null) {
            $hash = hash('sha256', HanoiCheckClient::encode([$link->transporter->toHanoiCheckRecord()]));
            $state = $this->state->handle(new GetHanoiCheckSyncStateQuery(SyncEntity::Transporter, $link->transporter->id, $environment, SyncHanoiCheckTransporterAction::PATH));
            $upToDate = $state['synced'] && $state['last']->payload_hash === $hash;

            if (! $upToDate) {
                $this->transporters->handle($link->transporter, $attempt);

                return ['waiting' => 'Đang chờ HanoiCheck ghi xong người giao hàng vừa đồng bộ.', 'problems' => []];
            }
            if ($recent($state)) {
                return ['waiting' => 'Đang chờ HanoiCheck ghi xong người giao hàng vừa đồng bộ.', 'problems' => []];
            }
        }

        return ['waiting' => null, 'problems' => $problems];
    }

    private function pushProcess(HanoiCheckOrderLink $link, array $lines, int $attempt): void
    {
        $body = [];
        if ($link->transporter !== null) {
            $body['ma_nguoi_giao'] = $link->transporter->code;
        }

        $photos = collect($link->photos())->pluck('url')->filter(fn ($url) => HanoiCheckPayloadBuilder::publicFileUrl($url) && ! str_ends_with(strtolower(strtok($url, '?')), '.pdf'))->take(HanoiCheckOrderLink::MAX_PHOTOS)->values();
        if ($photos->isNotEmpty()) {
            $body['danh_sach_anh'] = $photos->map(fn ($url) => ['duong_dan' => $url])->all();
        }

        $details = collect($lines)
            ->filter(fn ($l) => $l['error'] === null && $l['phan_bo'] !== [])
            ->map(fn ($l) => ['trace_code' => $l['trace_code'], 'phan_bo' => $l['phan_bo']])
            ->values()->all();
        if ($details !== []) {
            $body['chi_tiet'] = $details;
        }

        if ($body === []) {
            return;
        }

        $hash = hash('sha256', HanoiCheckClient::encode($body));
        if ($hash === $link->process_hash) {
            return;
        }

        $this->call->handle(SyncEntity::Order, $link->id, '/api/supplier/orders/' . rawurlencode($link->hanoicheck_order_code) . '/process', $body, $attempt);
        $link->process_hash = $hash;
    }

    private function advanceStatus(HanoiCheckOrderLink $link, ?RemoteOrderStatus $current, ?RemoteOrderStatus $target, bool $complete, int $attempt): array
    {
        if ($target === null || $current?->rank() === null || $current->rank() >= $target->rank()) {
            return [];
        }

        $problems = [];
        $reachable = $target;
        if ($target->rank() >= RemoteOrderStatus::DangGiao->rank()) {
            if (! $complete) {
                $problems[] = 'Chưa chuyển sang "Đang giao": mọi dòng phải khai đủ nguồn hàng đúng bằng số lượng trường đặt.';
            }
            if ($link->transporter === null) {
                $problems[] = 'Chưa chuyển sang "Đang giao": chưa chọn người giao hàng.';
            }
            if ($problems !== []) {
                $reachable = RemoteOrderStatus::DangChuanBi;
            }
        }
        if ($reachable === RemoteOrderStatus::DaGiao && count($link->photos()) === 0) {
            $problems[] = 'Chưa chuyển sang "Đã giao hàng": cần 1–3 ảnh tổng quan đơn.';
            $reachable = RemoteOrderStatus::DangGiao;
        }

        $code = rawurlencode($link->hanoicheck_order_code);
        for ($guard = 0; $guard < 4 && $current->rank() < $reachable->rank(); $guard++) {
            $options = collect($this->client->get("/api/supplier/orders/{$code}/status-options")['json']['data']['status_options'] ?? [])->pluck('value');
            $next = collect(RemoteOrderStatus::cases())
                ->first(fn (RemoteOrderStatus $s) => $s->rank() === $current->rank() + 1 && $options->contains($s->value));
            if ($next === null) {
                $problems[] = "HanoiCheck không cho chuyển đơn từ \"{$current->label()}\" sang bước tiếp theo lúc này.";

                break;
            }

            $this->call->handle(SyncEntity::Order, $link->id, "/api/supplier/orders/{$code}/status", [
                'trang_thai' => $next->value,
                'ghi_chu'    => 'Cập nhật tự động từ hệ thống VISAFO',
            ], $attempt);
            $current = $next;
            $link->remote_status = $current;
        }

        return $problems;
    }

    private function finish(HanoiCheckOrderLink $link, OrderSyncStatus $status, ?string $message): OrderSyncStatus
    {
        $link->forceFill([
            'sync_status'  => $status,
            'sync_message' => $message ? mb_substr($message, 0, 5000) : null,
            'synced_at'    => in_array($status, [OrderSyncStatus::Synced, OrderSyncStatus::Incomplete, OrderSyncStatus::Locked], true) ? now() : $link->synced_at,
        ])->save();

        return $status;
    }
}
