<?php

namespace Modules\Compliance\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Data\Requests\CompanyProfileData;
use Modules\Compliance\Models\InternalFacility;

class UpdateCompanyProfileAction
{
    use AsAction;

    public function handle(InternalFacility $headquarter, CompanyProfileData $data): InternalFacility
    {
        $headquarter->update([
            'company_name'         => $data->company_name,
            'company_type'         => $data->company_type,
            'tax_code'             => $data->tax_code,
            'tax_code_issue_date'  => $data->tax_code_issue_date,
            'tax_code_issue_place' => $data->tax_code_issue_place,
            'province_code'        => $data->province_code,
            'ward_code'            => $data->ward_code,
            'address'              => $data->address,
        ]);

        return $headquarter;
    }
}
