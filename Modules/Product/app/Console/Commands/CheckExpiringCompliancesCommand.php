<?php

namespace Modules\Product\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Product\Enums\ComplianceStatus;
use Modules\Product\Models\ProductCompliance;
use Modules\Product\Notifications\ProductComplianceExpiringNotification;

class CheckExpiringCompliancesCommand extends Command
{
    protected $signature = 'product:compliances-expiry-check';

    protected $description = 'Quét product_compliances sắp hết hạn (<= 30 ngày) và gửi cảnh báo tới user có quyền product.manage; đồng thời chuyển trạng thái các hồ sơ đã hết hạn.';

    public function handle(): int
    {
        $expiredCount = ProductCompliance::query()
            ->where('status', ComplianceStatus::Active->value)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now()->toDateString())
            ->update(['status' => ComplianceStatus::Expired->value]);

        $compliances = ProductCompliance::query()
            ->where('status', ComplianceStatus::Active->value)
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->get();

        $sent       = 0;
        $recipients = User::all()->filter(fn (User $user) => $user->can('product.manage'));

        foreach ($compliances as $compliance) {
            foreach ($recipients as $recipient) {
                $recipient->notify(new ProductComplianceExpiringNotification($compliance));
                $sent++;
            }
        }

        $this->info("Đã chuyển {$expiredCount} hồ sơ sang trạng thái hết hạn.");
        $this->info("Đã gửi {$sent} cảnh báo sắp hết hạn.");

        return self::SUCCESS;
    }
}
