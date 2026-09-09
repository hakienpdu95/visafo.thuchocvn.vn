<?php

namespace Modules\Employee\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Models\EmployeeHealthRecord;

class DestroyEmployeeHealthRecordAction
{
    use AsAction;

    public function handle(EmployeeHealthRecord $record): void
    {
        $record->delete();
    }
}
