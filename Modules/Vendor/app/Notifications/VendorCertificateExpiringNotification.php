<?php

namespace Modules\Vendor\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Vendor\Models\VendorCertificate;

class VendorCertificateExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly VendorCertificate $certificate,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $vendorName = $this->certificate->vendor()->withoutTenant()->first()->name;
        $typeLabel  = $this->certificate->certificate_type->label();

        return [
            'type'     => 'vendor_certificate_expiring',
            'title'    => 'Chứng chỉ sắp hết hạn',
            'message'  => "{$typeLabel} của nhà cung cấp \"{$vendorName}\" sẽ hết hạn vào {$this->certificate->expiry_date->format('d/m/Y')}.",
            'url'      => route('backend.vendors.show', $this->certificate->vendor_id),
            'icon'     => 'alert-triangle',
            'severity' => 'warning',
        ];
    }
}
