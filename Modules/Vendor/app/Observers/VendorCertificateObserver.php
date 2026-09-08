<?php

namespace Modules\Vendor\Observers;

use Modules\Vendor\Models\VendorCertificate;

class VendorCertificateObserver
{
    public function creating(VendorCertificate $certificate): void
    {
        if (! $certificate->is_active) {
            return;
        }

        VendorCertificate::where('vendor_id', $certificate->vendor_id)
            ->where('certificate_type', $certificate->certificate_type instanceof \BackedEnum
                ? $certificate->certificate_type->value
                : $certificate->certificate_type)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
