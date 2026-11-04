<?php

namespace Modules\HanoiCheck\Console\Commands;

use Illuminate\Console\Command;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Jobs\SyncToHanoiCheckJob;
use Modules\HanoiCheck\Actions\Backend\SyncHanoiCheckRecordAction;
use Throwable;

class HanoiCheckSyncCommand extends Command
{
    protected $signature = 'hanoicheck:sync {entity : master|vendor|product|product_batch} {--id=* : ID bản ghi} {--all : Toàn bộ bản ghi} {--now : Chạy ngay, không qua queue}';

    protected $description = 'Đồng bộ dữ liệu lên HanoiCheck theo đúng trình tự phụ thuộc';

    public function handle(SyncHanoiCheckRecordAction $action): int
    {
        $entity = SyncEntity::tryFrom($this->argument('entity'));
        if ($entity === null || in_array($entity, [SyncEntity::GoodsReceipt, SyncEntity::PrintLog], true)) {
            $this->error('Entity hợp lệ: master, vendor, product, product_batch');

            return self::INVALID;
        }

        $ids = $entity === SyncEntity::Master
            ? collect([SyncHanoiCheckRecordAction::MASTER_ID])
            : ($this->option('all') ? $entity->modelClass()::query()->pluck('id') : collect($this->option('id')));

        if ($ids->isEmpty()) {
            $this->error('Truyền --id=... hoặc --all.');

            return self::INVALID;
        }

        $counts = [];
        foreach ($ids as $id) {
            if (! $this->option('now')) {
                SyncToHanoiCheckJob::dispatch($entity->value, (string) $id);
                $counts['queued'] = ($counts['queued'] ?? 0) + 1;

                continue;
            }

            try {
                $status = $action->handle($entity, (string) $id)->value;
            } catch (Throwable $e) {
                $status = 'failed';
                $this->warn("{$id}: {$e->getMessage()}");
            }
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        $this->table(['Kết quả', 'Số bản ghi'], collect($counts)->map(fn ($n, $k) => [$k, $n])->values()->all());

        return self::SUCCESS;
    }
}
