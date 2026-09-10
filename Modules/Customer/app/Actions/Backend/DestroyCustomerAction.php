<?php

namespace Modules\Customer\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Models\Customer;

class DestroyCustomerAction
{
    use AsAction;

    public function handle(Customer $customer): string
    {
        $name = $customer->name;
        $customer->delete();

        return $name;
    }
}
