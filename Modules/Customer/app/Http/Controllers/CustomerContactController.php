<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Customer\Actions\Backend\DestroyCustomerContactAction;
use Modules\Customer\Actions\Backend\StoreCustomerContactAction;
use Modules\Customer\Data\Requests\StoreCustomerContactData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerContact;

class CustomerContactController extends Controller
{
    public function store(Request $request, Customer $customer, StoreCustomerContactAction $action): RedirectResponse
    {
        $this->authorize('update', $customer);

        $data = StoreCustomerContactData::validateAndCreate($request->all());
        $action->handle($customer, $data);

        return redirect()->route('backend.customers.show', $customer)
            ->with('success', 'Đã thêm đầu mối liên hệ mới.');
    }

    public function destroy(Customer $customer, CustomerContact $contact, DestroyCustomerContactAction $action): RedirectResponse
    {
        $this->authorize('update', $customer);

        $action->handle($contact);

        return redirect()->route('backend.customers.show', $customer)
            ->with('success', 'Đã xóa đầu mối liên hệ.');
    }
}
