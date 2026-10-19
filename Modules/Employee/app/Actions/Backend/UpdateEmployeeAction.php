<?php

namespace Modules\Employee\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\UpdateEmployeeData;
use Modules\Employee\Models\Employee;

class UpdateEmployeeAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(Employee $employee, UpdateEmployeeData $data): Employee
    {
        $employee->update($data->profileAttributes());

        $employee->departments()->sync($data->department_ids);

        if ($data->avatar !== null) {
            $this->uploadService->bulkDelete($employee, 'avatar');
            $this->uploadService->upload($data->avatar, $employee, 'avatar');
        }

        foreach (Employee::ID_CARD_COLLECTIONS as $collection) {
            if ($data->{$collection} !== null || $data->{'remove_' . $collection}) {
                $this->uploadService->bulkDelete($employee, $collection);
            }
            if ($data->{$collection} !== null) {
                $this->uploadService->upload($data->{$collection}, $employee, $collection);
            }
        }

        return $employee;
    }
}
