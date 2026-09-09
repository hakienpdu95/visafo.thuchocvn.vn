<?php

namespace Modules\Employee\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\StoreEmployeeHealthRecordData;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeHealthRecord;

class StoreEmployeeHealthRecordAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(Employee $employee, StoreEmployeeHealthRecordData $data): EmployeeHealthRecord
    {
        $expiryDate = $data->expiry_date
            ?? now()->parse($data->issue_date)->addMonths($data->record_type->defaultValidityMonths())->toDateString();

        $record = $employee->healthRecords()->create([
            'record_type'         => $data->record_type->value,
            'issue_date'          => $data->issue_date,
            'expiry_date'         => $expiryDate,
            'result'              => $data->result,
            'certificate_number'  => $data->certificate_number,
            'issued_by'           => $data->issued_by,
            'notes'               => $data->notes,
        ]);

        if ($data->file !== null) {
            $this->uploadService->upload($data->file, $record, 'attachments_private');
        }

        return $record;
    }
}
