<?php

namespace Modules\Customer\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Models\CustomerContact;

class DestroyCustomerContactAction
{
    use AsAction;

    public function handle(CustomerContact $contact): void
    {
        $contact->delete();
    }
}
