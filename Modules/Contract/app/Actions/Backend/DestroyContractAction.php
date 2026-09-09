<?php

namespace Modules\Contract\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Contract\Models\Contract;

class DestroyContractAction
{
    use AsAction;

    public function handle(Contract $contract): string
    {
        $name = $contract->name;
        $contract->delete();

        return $name;
    }
}
