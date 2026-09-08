<?php

namespace Modules\Organization\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Organization\Data\Requests\UpdateOrganizationData;
use Modules\Organization\Events\OrganizationUpdated;
use Modules\Organization\Models\Organization;

class UpdateOrganizationAction
{
    use AsAction;

    public function handle(Organization $organization, UpdateOrganizationData $data): Organization
    {
        $organization->update([
            'name'          => $data->name,
            'slug'          => $data->slug ?? $organization->slug,
            'code'          => $data->code,
            'status'        => $data->status->value,
            'locale'        => $data->locale,
            'timezone'      => $data->timezone,
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

        event(new OrganizationUpdated($organization));

        return $organization;
    }
}
