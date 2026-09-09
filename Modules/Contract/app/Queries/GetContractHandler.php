<?php

namespace Modules\Contract\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Contract\Models\Contract;

class GetContractHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Contract
    {
        /** @var GetContractQuery $query */
        $contract = $query->contract;

        $contract->load(['vendor', 'contractType']);

        return $contract;
    }
}
