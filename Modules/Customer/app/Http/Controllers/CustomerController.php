<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Customer\Actions\Backend\DestroyCustomerAction;
use Modules\Customer\Actions\Backend\StoreCustomerAction;
use Modules\Customer\Actions\Backend\UpdateCustomerAction;
use Modules\Customer\Data\Requests\StoreCustomerData;
use Modules\Customer\Data\Requests\UpdateCustomerData;
use Modules\Customer\Enums\CustomerGroup;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\MealModel;
use Modules\Customer\Models\Customer;
use Modules\Customer\Queries\GetCustomerHandler;
use Modules\Customer\Queries\GetCustomerQuery;
use Modules\Employee\Models\Employee;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index()
    {
        $statuses = collect(CustomerStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $customerGroups = collect(CustomerGroup::cases())
            ->map(fn ($g) => ['value' => $g->value, 'text' => $g->label()])
            ->all();

        $mealModels = collect(MealModel::cases())
            ->map(fn ($m) => ['value' => $m->value, 'text' => $m->label()])
            ->all();

        return view('customer::index', compact('statuses', 'customerGroups', 'mealModels'));
    }

    public function create()
    {
        $employees = Employee::query()->orderBy('full_name')->get(['id', 'full_name']);

        return view('customer::create', compact('employees'));
    }

    public function store(Request $request, StoreCustomerAction $action): RedirectResponse
    {
        $data     = StoreCustomerData::validateAndCreate($request->all());
        $customer = $action->handle($data);

        return redirect()->route('backend.customers.show', $customer)
            ->with('success', 'Khách hàng "' . $customer->name . '" đã được tạo thành công.');
    }

    public function show(Customer $customer, GetCustomerHandler $handler)
    {
        $customer = $handler->handle(new GetCustomerQuery($customer));

        return view('customer::show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $employees = Employee::query()->orderBy('full_name')->get(['id', 'full_name']);

        return view('customer::edit', compact('customer', 'employees'));
    }

    public function update(Request $request, Customer $customer, UpdateCustomerAction $action): RedirectResponse
    {
        $data = UpdateCustomerData::validateAndCreate($request->all());
        $action->handle($customer, $data);

        return redirect()->route('backend.customers.show', $customer)
            ->with('success', 'Cập nhật khách hàng thành công.');
    }

    public function destroy(Request $request, Customer $customer, DestroyCustomerAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($customer);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa khách hàng "' . $name . '".']);
        }

        return redirect()->route('backend.customers.index')
            ->with('success', 'Đã xóa khách hàng "' . $name . '".');
    }
}
