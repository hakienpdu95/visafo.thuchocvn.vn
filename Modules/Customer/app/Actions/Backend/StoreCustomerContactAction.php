<?php

namespace Modules\Customer\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Data\Requests\StoreCustomerContactData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerContact;

class StoreCustomerContactAction
{
    use AsAction;

    public function handle(Customer $customer, StoreCustomerContactData $data): CustomerContact
    {
        return $customer->contacts()->create([
            'name'  => $data->name,
            'title' => $data->title,
            'phone' => $data->phone,
            'email' => $data->email,
            'note'  => $data->note,
        ]);
    }
}
