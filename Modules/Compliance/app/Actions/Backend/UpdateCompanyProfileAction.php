<?php

namespace Modules\Compliance\Actions\Backend;

use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use App\Support\Html\RichHtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Data\Requests\CompanyProfileData;
use Modules\Compliance\Models\InternalFacility;

class UpdateCompanyProfileAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
    ) {}

    public function handle(InternalFacility $headquarter, CompanyProfileData $data): InternalFacility
    {
        DB::transaction(fn () => $this->save($headquarter, $data));

        return $headquarter;
    }

    private function save(InternalFacility $headquarter, CompanyProfileData $data): void
    {
        $role = RichHtmlSanitizer::clean($data->supply_chain_role);

        $headquarter->update([
            'company_name'         => $data->company_name,
            'company_type'         => $data->company_type,
            'tax_code'             => $data->tax_code,
            'tax_code_issue_date'  => $data->tax_code_issue_date,
            'tax_code_issue_place' => $data->tax_code_issue_place,
            'province_code'        => $data->province_code,
            'ward_code'            => $data->ward_code,
            'address'              => $data->address,
            'supply_chain_role'    => $role,
        ]);

        // Ảnh chèn qua Jodit đang nằm tạm ở JoditDraft → nhận về trụ sở chính (chuyển file), gỡ ảnh đã bỏ khỏi nội dung,
        // rồi cập nhật lại src nhúng theo vị trí file mới.
        preg_match_all('/data-media-uuid="([^"]+)"/', (string) $role, $m);
        $this->mediaUpload->reassociateOrphans($headquarter, array_values(array_unique($m[1])));
        $refreshed = $this->mediaUrl->refreshEmbeddedImageUrls($role);
        if ($refreshed !== $role) {
            $headquarter->update(['supply_chain_role' => $refreshed]);
        }
    }
}
