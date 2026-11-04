<?php

namespace Modules\HanoiCheckOrder\Console\Commands;

use Illuminate\Console\Command;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\HanoiCheck\Actions\Backend\SyncHanoiCheckRecordAction;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Jobs\SyncToHanoiCheckJob;
use Modules\HanoiCheck\Queries\GetHanoiCheckCatalogHandler;
use Modules\HanoiCheck\Queries\GetHanoiCheckCatalogQuery;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheck\Services\HanoiCheckTokenService;
use Modules\HanoiCheckOrder\Jobs\SyncHanoiCheckOrderJob;
use Modules\HanoiCheckOrder\Jobs\SyncHanoiCheckTransporterJob;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\HanoiCheckOrder\Models\HanoiCheckTransporter;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Product;
use Modules\Vendor\Enums\VendorStatus;
use Modules\Vendor\Models\Vendor;
use Throwable;

class HanoiCheckGoLiveCommand extends Command
{
    protected $signature = 'hanoicheck:go-live {--check : Chỉ kiểm tra cấu hình + kết nối, không đẩy dữ liệu}';

    protected $description = 'Kích hoạt kết nối HanoiCheck: kiểm tra cấu hình, token, danh mục, rồi đồng bộ toàn bộ dữ liệu theo đúng trình tự';

    public function handle(HanoiCheckTokenService $tokens, GetHanoiCheckCatalogHandler $catalog, SyncHanoiCheckRecordAction $records): int
    {
        $cfg = config('services.hanoicheck');
        $checks = [
            ['HANOICHECK_ENABLED=true', (bool) $cfg['enabled']],
            ['HANOICHECK_FAKE=false', ! $cfg['fake']],
            ['HANOICHECK_BASE_URL', filled($cfg['base_url']) && ! str_ends_with($cfg['base_url'], '/')],
            ['HANOICHECK_CLIENT_ID', filled($cfg['client_id'])],
            ['HANOICHECK_CLIENT_SECRET', filled($cfg['client_secret'])],
            ['HANOICHECK_HMAC_SECRET (khác client_secret)', filled($cfg['hmac_secret']) && $cfg['hmac_secret'] !== $cfg['client_secret']],
            ['Danh mục thực phẩm (HANOICHECK_DEFAULT_FOOD_CATEGORY_ID hoặc CATEGORY_MAP)', filled($cfg['defaults']['ma_loai_sp']) || ! empty($cfg['defaults']['category_map'])],
            ['Nhóm thực phẩm NCC (HANOICHECK_DEFAULT_FOOD_GROUP_IDS hoặc CATEGORY_MAP)', ! empty($cfg['defaults']['nhom_thuc_pham']) || ! empty($cfg['defaults']['category_map'])],
        ];
        $this->table(['Cấu hình', 'OK'], array_map(fn ($c) => [$c[0], $c[1] ? '✔' : '✘'], $checks));

        $required = array_slice($checks, 0, 6);
        if (collect($required)->contains(fn ($c) => ! $c[1])) {
            $this->error('Thiếu cấu hình kết nối. Cập nhật .env rồi chạy php artisan config:cache và chạy lại lệnh này.');

            return self::FAILURE;
        }

        try {
            $tokens->forgetAll();
            $tokens->token();
            $this->info('✔ Lấy token thành công.');

            $foods = collect($catalog->handle(new GetHanoiCheckCatalogQuery('standard-foods')));
            $this->info("✔ Danh mục thực phẩm chuẩn: {$foods->count()} mục.");
            $schools = $catalog->handle(new GetHanoiCheckCatalogQuery('schools'));
            $this->info('✔ Chữ ký dữ liệu hợp lệ — trường được phân công: ' . count($schools) . '.');
        } catch (Throwable $e) {
            $this->error('Kết nối thất bại: ' . $e->getMessage());

            return self::FAILURE;
        }

        $ids = collect([$cfg['defaults']['ma_loai_sp']])->merge(array_values($cfg['defaults']['category_map']))->merge($cfg['defaults']['nhom_thuc_pham'])->filter()->map(fn ($v) => (string) $v)->unique();
        $unknown = $ids->diff($foods->pluck('id')->map(fn ($v) => (string) $v));
        if ($unknown->isNotEmpty()) {
            $this->warn('Các id danh mục sau KHÔNG có trong danh mục chuẩn của môi trường này: ' . $unknown->implode(', ') . ' — sản phẩm/NCC dùng id này sẽ bị Sở từ chối.');
        }

        if ($this->option('check')) {
            $this->info('Chế độ --check: không đẩy dữ liệu.');

            return self::SUCCESS;
        }

        $master = $records->handle(SyncEntity::Master, SyncHanoiCheckRecordAction::MASTER_ID);
        $this->info("✔ Dữ liệu nền (cơ sở, kho, khâu, quy trình): {$master->value}.");

        $queued = [
            'NCC đầu vào'      => $this->queue(SyncEntity::Vendor, Vendor::query()->where('status', VendorStatus::Active->value)->pluck('id')),
            'Thực phẩm'        => $this->queue(SyncEntity::Product, Product::query()->where('status', ProductStatus::Active->value)->pluck('id')),
            'Lô sản xuất'      => $this->queue(SyncEntity::ProductBatch, ProductBatch::query()->pluck('id')),
        ];

        $transporters = HanoiCheckTransporter::query()->pluck('id');
        $transporters->each(fn ($id) => SyncHanoiCheckTransporterJob::dispatch($id));
        $queued['Người giao hàng'] = $transporters->count();

        $links = HanoiCheckOrderLink::query()->pluck('id');
        $links->each(fn ($id) => SyncHanoiCheckOrderJob::dispatch($id)->delay(now()->addMinutes(2)));
        $queued['Đơn đã liên kết (chạy sau 2 phút)'] = $links->count();

        $this->table(['Đã xếp hàng trên queue "' . $cfg['queue'] . '"', 'Số bản ghi'], collect($queued)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info('Theo dõi: bảng hanoicheck_sync_logs, storage/logs/hanoicheck-*.log và trang dashboard/hanoicheck-orders.');

        return self::SUCCESS;
    }

    private function queue(SyncEntity $entity, $ids): int
    {
        $ids->each(fn ($id) => SyncToHanoiCheckJob::dispatch($entity->value, (string) $id));

        return $ids->count();
    }
}
