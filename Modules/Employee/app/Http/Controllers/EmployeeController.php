<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Employee\Actions\Backend\DestroyEmployeeAction;
use Modules\Employee\Actions\Backend\StoreEmployeeAction;
use Modules\Employee\Actions\Backend\UpdateEmployeeAction;
use Modules\Employee\Data\Requests\StoreEmployeeData;
use Modules\Employee\Data\Requests\UpdateEmployeeData;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Employee;
use Modules\Employee\Queries\GetEmployeeHandler;
use Modules\Employee\Queries\GetEmployeeQuery;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Employee::class, 'employee');
    }

    public function index()
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('employee::employees.index', compact('departments'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('employee::employees.create', compact('departments'));
    }

    public function store(Request $request, StoreEmployeeAction $action): RedirectResponse
    {
        $data     = StoreEmployeeData::validateAndCreate($request->all());
        $employee = $action->handle($data);

        return redirect()->route('backend.employees.edit', $employee)
            ->with('success', 'Đã thêm nhân viên "' . $employee->full_name . '".');
    }

    public function edit(Employee $employee, GetEmployeeHandler $handler)
    {
        $employee    = $handler->handle(new GetEmployeeQuery($employee));
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('employee::employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, Employee $employee, UpdateEmployeeAction $action): RedirectResponse
    {
        $data = UpdateEmployeeData::validateAndCreate($request->all());
        $action->handle($employee, $data);

        return redirect()->route('backend.employees.edit', $employee)
            ->with('success', 'Cập nhật nhân viên thành công.');
    }

    public function destroy(Employee $employee, DestroyEmployeeAction $action): RedirectResponse
    {
        $action->handle($employee);

        return redirect()->route('backend.employees.index')
            ->with('success', 'Đã xóa nhân viên.');
    }
}
