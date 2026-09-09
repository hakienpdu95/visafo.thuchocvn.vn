<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Employee\Actions\Backend\DestroyEmployeeHealthRecordAction;
use Modules\Employee\Actions\Backend\StoreEmployeeHealthRecordAction;
use Modules\Employee\Data\Requests\StoreEmployeeHealthRecordData;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeHealthRecord;

class EmployeeHealthRecordController extends Controller
{
    private const NULLABLE_FIELDS = ['expiry_date', 'result', 'certificate_number', 'issued_by', 'notes'];

    public function store(Request $request, Employee $employee, StoreEmployeeHealthRecordAction $action): RedirectResponse
    {
        $this->authorize('update', $employee);

        $input = $request->all();
        foreach (self::NULLABLE_FIELDS as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $data = StoreEmployeeHealthRecordData::validateAndCreate($input);
        $action->handle($employee, $data);

        return redirect()->route('backend.employees.edit', $employee)
            ->with('success', 'Đã thêm hồ sơ mới cho nhân viên.');
    }

    public function destroy(Employee $employee, EmployeeHealthRecord $healthRecord, DestroyEmployeeHealthRecordAction $action): RedirectResponse
    {
        $this->authorize('update', $employee);

        $action->handle($healthRecord);

        return redirect()->route('backend.employees.edit', $employee)
            ->with('success', 'Đã xóa hồ sơ.');
    }
}
