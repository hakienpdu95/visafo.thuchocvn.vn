<?php

namespace Modules\Organization\Actions;

use App\Shared\Tenancy\Enums\OrganizationStatus;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Organization\Enums\OrganizationVersionStatus;
use Modules\Organization\Models\Organization;
use Modules\Organization\Models\OrganizationVersion;

/**
 * SRS01-FR-ORG-008 (GAP_ANALYSIS_v1.0.md §3.3 ORG-03): "deployment snapshot" khi
 * activate/publish config tổ chức — tái dùng ý tưởng CreateConfigSnapshotAction
 * (Modules/Assessment). Version mới luôn tạo 'active', bản active cũ (nếu có)
 * chuyển 'superseded' — Approved/Active immutable (SRS01-BR-003-ish): sửa phải
 * qua version mới, không patch version cũ.
 */
class PublishOrganizationConfigAction
{
    use AsAction;

    public function handle(Organization $organization, ?string $changeReason = null): OrganizationVersion
    {
        return DB::transaction(function () use ($organization, $changeReason) {
            $configJson = $this->buildConfigSnapshot($organization);
            $sorted     = $configJson;
            ksort($sorted);
            $checksum = hash('sha256', json_encode($sorted, JSON_UNESCAPED_UNICODE));

            $nextVersion = (int) OrganizationVersion::withoutTenant()
                ->where('organization_id', $organization->id)
                ->max('version') + 1;

            OrganizationVersion::withoutTenant()
                ->where('organization_id', $organization->id)
                ->where('status', OrganizationVersionStatus::Active->value)
                ->update(['status' => OrganizationVersionStatus::Superseded->value]);

            $version = OrganizationVersion::create([
                'organization_id' => $organization->id,
                'version'         => $nextVersion,
                'config_json'     => $configJson,
                'checksum'        => $checksum,
                'status'          => OrganizationVersionStatus::Active->value,
                'effective_at'    => now(),
                'change_reason'   => $changeReason,
                'created_by'      => auth()->id(),
                'approved_by'     => auth()->id(),
                'approved_at'     => now(),
            ]);

            // Lần publish đầu tiên: Draft/PendingApproval -> Active (SRS01-UC-01 bước 6-7).
            if ($organization->status->canTransitionTo(OrganizationStatus::Active)) {
                $organization->update([
                    'status'       => OrganizationStatus::Active->value,
                    'activated_at' => $organization->activated_at ?? now(),
                ]);
            }

            return $version;
        });
    }

    /** @return array<string,mixed> */
    private function buildConfigSnapshot(Organization $organization): array
    {
        return [
            'name'                 => $organization->name,
            'code'                 => $organization->code,
            'slug'                 => $organization->slug,
            'locale'               => $organization->locale,
            'timezone'             => $organization->timezone,
            'purpose'              => $organization->purpose,
            'retention_policy_id'  => $organization->retention_policy_id,
            'settings'             => $organization->settings,
        ];
    }
}
