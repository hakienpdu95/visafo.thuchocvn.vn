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
        $employee = Employee::create($data->profileAttributes());

        $employee->departments()->sync($data->department_ids);

        if ($data->avatar !== null) {
            $this->uploadService->upload($data->avatar, $employee, 'avatar');
        }

        foreach (Employee::ID_CARD_COLLECTIONS as $collection) {
            if ($data->{$collection} !== null) {
                $this->uploadService->upload($data->{$collection}, $employee, $collection);
            }
        }

        return $employee;
    }
}
