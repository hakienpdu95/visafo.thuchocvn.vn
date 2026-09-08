<?php

namespace Modules\Organization\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Organization\Data\Requests\StoreOrganizationData;
use Modules\Organization\Events\OrganizationCreated;
use Modules\Organization\Models\Organization;

class StoreOrganizationAction
{
    use AsAction;

    public function handle(StoreOrganizationData $data): Organization
    {
        $organization = Organization::create([
            'name'          => $data->name,
            'slug'          => $data->slug ?? Organization::generateSlug($data->name),
            'code'          => $data->code,
            'status'        => $data->status->value,
            'locale'        => $data->locale ?? 'vi',
            'timezone'      => $data->timezone ?? 'Asia/Ho_Chi_Minh',
            'purpose'       => $data->purpose,
            'retention_policy_id' => $data->retention_policy_id,
            'tax_code'      => $data->tax_code,
            'phone'         => $data->phone,
            'email'         => $data->email,
            'website'       => $data->website,
            'industry'      => $data->industry,
            'description'   => sanitize_rich_text($data->description),
            'logo_path'     => $data->logo_path,
            'province_code' => $data->province_code,
            'ward_code'     => $data->ward_code,
            'full_address'  => $data->full_address,
            'address'       => $data->address,
            'city'          => $data->city,
            'country'       => $data->country,
            'postal_code'   => $data->postal_code,
        ]);

        event(new OrganizationCreated($organization));

        return $organization;
    }
}
