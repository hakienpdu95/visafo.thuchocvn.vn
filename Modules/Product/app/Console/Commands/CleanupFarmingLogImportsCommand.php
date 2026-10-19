<?php

namespace Modules\Product\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Http\Controllers\FarmingLogImportController;

class CleanupFarmingLogImportsCommand extends Command
{
    protected $signature = 'farming-logs:cleanup-temp {--hours=24 : Xóa file tạm cũ hơn số giờ này}';

    protected $description = 'Xóa file Excel tạm của luồng import nhật ký canh tác bị bỏ dở';

    public function handle(): int
    {
        $disk = Storage::disk(FarmingLogImportController::DISK);
        $threshold = now()->subHours(max(0, (int) $this->option('hours')))->getTimestamp();
        $deleted = 0;

        foreach ($disk->files(FarmingLogImportController::DIR) as $file) {
            if ($disk->lastModified($file) < $threshold) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Đã xóa {$deleted} file import tạm.");

        return self::SUCCESS;
    }
}
