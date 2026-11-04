<?php

namespace Modules\HanoiCheck\Console\Commands;

use Illuminate\Console\Command;
use Modules\HanoiCheck\Queries\GetHanoiCheckCatalogHandler;
use Modules\HanoiCheck\Queries\GetHanoiCheckCatalogQuery;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Throwable;

class HanoiCheckLookupCommand extends Command
{
    protected $signature = 'hanoicheck:lookup {catalog : standard-foods|student-groups|meal-types|measures|schools} {--search= : Lọc theo tên} {--json}';

    protected $description = 'Tra cứu danh mục dùng chung HanoiCheck (Phần V)';

    public function handle(GetHanoiCheckCatalogHandler $handler): int
    {
        if (HanoiCheckClient::fake()) {
            $this->warn('HANOICHECK_FAKE=true: không gọi HTTP. Đặt HANOICHECK_FAKE=false để tra cứu thật.');

            return self::SUCCESS;
        }

        try {
            $rows = collect($handler->handle(new GetHanoiCheckCatalogQuery($this->argument('catalog'), $this->option('search'))));
        } catch (Throwable $e) {
            $this->error('Tra cứu thất bại: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->table(['id', 'name', 'measure_name'], $rows->map(fn ($r) => [$r['id'] ?? '', $r['name'] ?? '', $r['measure_name'] ?? ''])->all());
        $this->info($rows->count() . ' dòng.');

        return self::SUCCESS;
    }
}
