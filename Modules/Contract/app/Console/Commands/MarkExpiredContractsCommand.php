<?php

namespace Modules\Contract\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Contract\Enums\ContractStatus;
use Modules\Contract\Models\Contract;

class MarkExpiredContractsCommand extends Command
{
    protected $signature = 'contracts:mark-expired';

    protected $description = 'Chuyển status=expired cho hợp đồng đang hiệu lực, không tự động gia hạn và đã quá end_date';

    public function handle(): int
    {
        $expired = 0;

        Contract::query()
            ->where('status', ContractStatus::Active->value)
            ->where('is_auto_renew', false)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', today())
            ->chunkById(200, function ($contracts) use (&$expired) {
                foreach ($contracts as $contract) {
                    $contract->update(['status' => ContractStatus::Expired->value]);
                    $expired++;
                }
            });

        if ($expired > 0) {
            Log::info('contracts:mark-expired — đã chuyển hợp đồng sang hết hạn', ['count' => $expired]);
        }

        $this->info("Đã chuyển {$expired} hợp đồng sang trạng thái hết hạn.");

        return self::SUCCESS;
    }
}
