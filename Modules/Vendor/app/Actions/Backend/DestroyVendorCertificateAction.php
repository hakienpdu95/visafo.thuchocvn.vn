<?php

namespace Modules\Vendor\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Vendor\Models\VendorCertificate;

class DestroyVendorCertificateAction
{
    use AsAction;

    public function handle(VendorCertificate $certificate): void
    {
        $certificate->delete();
    }
}
