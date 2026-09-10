<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Customer\Actions\Backend\DestroyCustomerDeliveryPointAction;
use Modules\Customer\Actions\Backend\StoreCustomerDeliveryPointAction;
use Modules\Customer\Data\Requests\StoreCustomerDeliveryPointData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerDeliveryPoint;

class CustomerDeliveryPointController extends Controller
{
    public function store(Request $request, Customer $customer, StoreCustomerDeliveryPointAction $action): RedirectResponse
    {
        $this->authorize('update', $customer);

        $data = StoreCustomerDeliveryPointData::validateAndCreate($request->all());
        $action->handle($customer, $data);

        return redirect()->route('backend.customers.show', $customer)
            ->with('success', 'Đã thêm điểm giao hàng mới.');
    }

    public function destroy(Customer $customer, CustomerDeliveryPoint $deliveryPoint, DestroyCustomerDeliveryPointAction $action): RedirectResponse
    {
        $this->authorize('update', $customer);

        $action->handle($deliveryPoint);

        return redirect()->route('backend.customers.show', $customer)
            ->with('success', 'Đã xóa điểm giao hàng.');
    }
}
