<?php

namespace Modules\Vendor\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Vendor\Data\Requests\StoreVendorCertificateData;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorCertificate;

class StoreVendorCertificateAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(Vendor $vendor, StoreVendorCertificateData $data): VendorCertificate
    {
        $certificate = $vendor->certificates()->create([
            'certificate_type'   => $data->certificate_type->value,
            'certificate_number' => $data->certificate_number,
            'issue_date'         => $data->issue_date,
            'expiry_date'        => $data->expiry_date,
            'issued_by'          => $data->issued_by,
            'renewal_deadline'   => $data->renewal_deadline,
            'is_active'          => true,
        ]);

        if ($data->file !== null) {
            $this->uploadService->upload($data->file, $certificate, 'attachments_private');
        }

        return $certificate;
    }
}
