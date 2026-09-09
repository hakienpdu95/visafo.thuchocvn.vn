<?php

namespace Modules\Employee\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\StoreEmployeeData;
use Modules\Employee\Models\Employee;

class StoreEmployeeAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(StoreEmployeeData $data): Employee
    {
        $employee = Employee::create([
            'full_name'    => $data->full_name,
            'email'        => $data->email,
            'phone'        => $data->phone,
            'facebook_url' => $data->facebook_url,
            'job_title'    => $data->job_title,
        ]);

        $employee->departments()->sync($data->department_ids);

        if ($data->avatar !== null) {
            $this->uploadService->upload($data->avatar, $employee, 'avatar');
        }

        return $employee;
    }
}
