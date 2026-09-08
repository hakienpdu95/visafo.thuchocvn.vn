<?php

namespace Modules\Product\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Product\Models\ProductCompliance;

class ProductComplianceExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ProductCompliance $compliance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $product      = $this->compliance->product()->withoutTenant()->first();
        $documentName = $this->compliance->documentType()->first()->name;

        return [
            'type'     => 'product_compliance_expiring',
            'title'    => 'Hồ sơ pháp lý sắp hết hạn',
            'message'  => "{$documentName} của sản phẩm \"{$product->name}\" sẽ hết hạn vào {$this->compliance->expiration_date->format('d/m/Y')}.",
            'url'      => route('backend.products.show', $this->compliance->product_id),
            'icon'     => 'alert-triangle',
            'severity' => 'warning',
        ];
    }
}
