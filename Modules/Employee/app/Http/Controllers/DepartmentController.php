<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Employee\Actions\Backend\DestroyDepartmentAction;
use Modules\Employee\Actions\Backend\StoreDepartmentAction;
use Modules\Employee\Actions\Backend\UpdateDepartmentAction;
use Modules\Employee\Data\Requests\StoreDepartmentData;
use Modules\Employee\Data\Requests\UpdateDepartmentData;
use Modules\Employee\Models\Department;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Department::class, 'department');
    }

    public function index()
    {
        $foodContactOptions = [
            ['value' => 'yes', 'text' => 'Có'],
            ['value' => 'no', 'text' => 'Không'],
        ];

        return view('employee::departments.index', compact('foodContactOptions'));
    }

    public function create()
    {
        return view('employee::departments.create');
    }

    public function store(Request $request, StoreDepartmentAction $action): RedirectResponse
    {
        $input                     = $request->all();
        $input['is_food_contact']  = $request->boolean('is_food_contact');

        $data       = StoreDepartmentData::validateAndCreate($input);
        $department = $action->handle($data);

        return redirect()->route('backend.departments.index')
            ->with('success', 'Đã thêm phòng ban "' . $department->name . '".');
    }

    public function edit(Department $department)
    {
        return view('employee::departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department, UpdateDepartmentAction $action): RedirectResponse
    {
        $input                    = $request->all();
        $input['is_food_contact'] = $request->boolean('is_food_contact');

        $data = UpdateDepartmentData::validateAndCreate($input);
        $action->handle($department, $data);

        return redirect()->route('backend.departments.index')
            ->with('success', 'Cập nhật phòng ban thành công.');
    }

    public function destroy(Department $department, DestroyDepartmentAction $action): RedirectResponse
    {
        $action->handle($department);

        return redirect()->route('backend.departments.index')
            ->with('success', 'Đã xóa phòng ban.');
    }
}
