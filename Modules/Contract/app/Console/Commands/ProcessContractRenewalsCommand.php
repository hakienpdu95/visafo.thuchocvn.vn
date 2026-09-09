<?php

namespace Modules\Contract\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Contract\Actions\Backend\RenewContractAction;
use Modules\Contract\Queries\DueForRenewalContractsHandler;
use Modules\Contract\Queries\DueForRenewalContractsQuery;

class ProcessContractRenewalsCommand extends Command
{
    protected $signature = 'contracts:process-renewals';

    protected $description = 'Tự động gia hạn các hợp đồng có is_auto_renew=true, status=active và end_date đã đến/qua hạn';

    public function handle(DueForRenewalContractsHandler $handler, RenewContractAction $action): int
    {
        $contracts = $handler->handle(new DueForRenewalContractsQuery());

        $renewed = 0;

        foreach ($contracts as $contract) {
            $oldEndDate = $contract->end_date->toDateString();

            $action->handle($contract);

            Log::info('contracts:process-renewals — đã gia hạn hợp đồng', [
                'contract_id'     => $contract->id,
                'contract_number' => $contract->contract_number,
                'old_end_date'    => $oldEndDate,
                'new_end_date'    => $contract->end_date->toDateString(),
            ]);

            $renewed++;
        }

        $this->info("Đã gia hạn {$renewed} hợp đồng.");

        return self::SUCCESS;
    }
}
