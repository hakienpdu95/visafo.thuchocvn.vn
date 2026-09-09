<?php

namespace Modules\Vendor\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Vendor\Models\VendorCertificate;
use Modules\Vendor\Notifications\VendorCertificateExpiringNotification;

class CheckExpiringCertificatesCommand extends Command
{
    protected $signature = 'vendor:certificates-expiry-check';

    protected $description = 'Quét vendor_certificates sắp hết hạn (<= 30 ngày) và gửi cảnh báo tới user có quyền vendor.manage';

    public function handle(): int
    {
        $certificates = VendorCertificate::query()
            ->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->get();

        $sent       = 0;
        $recipients = User::all()->filter(fn (User $user) => $user->can('vendor.manage'));

        foreach ($certificates as $certificate) {
            foreach ($recipients as $recipient) {
                $recipient->notify(new VendorCertificateExpiringNotification($certificate));
                $sent++;
            }
        }

        $this->info("Đã gửi {$sent} cảnh báo hết hạn chứng chỉ.");

        return self::SUCCESS;
    }
}
