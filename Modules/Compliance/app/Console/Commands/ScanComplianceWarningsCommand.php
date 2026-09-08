<?php

namespace Modules\Compliance\Console\Commands;

use Illuminate\Console\Command;
use Modules\Compliance\Enums\WarningCategory;
use Modules\Compliance\Enums\WarningSeverity;
use Modules\Compliance\Enums\WarningStatus;
use Modules\Compliance\Models\ComplianceWarning;
use Modules\Product\Enums\ComplianceStatus;
use Modules\Product\Enums\ProductCategoryType;
use Modules\Product\Models\Product;
use Modules\Vendor\Enums\VendorCertificateType;
use Modules\Vendor\Models\Vendor;
use Modules\Warehouse\Enums\BatchStatus;
use Modules\Warehouse\Models\Batch;

class ScanComplianceWarningsCommand extends Command
{
    protected $signature = 'compliance:scan-warnings';

    protected $description = 'Quét hồ sơ pháp lý sản phẩm, chứng chỉ nhà cung cấp và lô hàng cận date, cập nhật hộp thư cảnh báo compliance_warnings.';

    public function handle(): int
    {
        $productIds = $this->scanProductCompliances();
        $this->resolveStale(WarningCategory::ProductComplianceExpiry, $productIds);

        $vendorIds = $this->scanVendorCertificates();
        $this->resolveStale(WarningCategory::VendorCertificateExpiry, $vendorIds);

        $batchIds = $this->scanBatches();
        $this->resolveStale(WarningCategory::BatchNearExpiry, $batchIds);

        $total = count($productIds) + count($vendorIds) + count($batchIds);
        $this->info("Đã cập nhật {$total} cảnh báo đang hiệu lực.");

        return self::SUCCESS;
    }

    /** @return string[] */
    private function scanProductCompliances(): array
    {
        $activeIds = [];

        $products = Product::withoutTenant()
            ->with(['compliances' => fn ($q) => $q->where('status', ComplianceStatus::Active->value)
                ->whereNotNull('expiration_date')
                ->with('documentType')])
            ->get();

        foreach ($products as $product) {
            $threshold = $product->category_type === ProductCategoryType::Cosmetic
                ? (int) config('compliance.thresholds.product_compliance_cosmetic_days')
                : (int) config('compliance.thresholds.product_compliance_default_days');

            foreach ($product->compliances as $compliance) {
                $daysRemaining = $this->daysRemaining($compliance->expiration_date);

                if ($daysRemaining > $threshold) {
                    continue;
                }

                ComplianceWarning::withoutTenant()->updateOrCreate(
                    [
                        'warnable_type' => 'product_compliance',
                        'warnable_id'   => $compliance->id,
                        'category'      => WarningCategory::ProductComplianceExpiry->value,
                    ],
                    [
                        'organization_id' => $product->organization_id,
                        'title'           => "{$compliance->documentType->name} — {$product->name}",
                        'message'         => "Hồ sơ \"{$compliance->documentType->name}\" của sản phẩm \"{$product->name}\" (SKU {$product->sku}) sẽ hết hạn vào {$compliance->expiration_date->format('d/m/Y')}.",
                        'due_date'        => $compliance->expiration_date,
                        'severity'        => $this->severity($daysRemaining)->value,
                    ],
                );

                $activeIds[] = $compliance->id;
            }
        }

        return $activeIds;
    }

    /** @return string[] */
    private function scanVendorCertificates(): array
    {
        $activeIds = [];
        $gmpAndFoodSafety = [VendorCertificateType::Gmp->value, VendorCertificateType::FoodSafety->value];

        $vendors = Vendor::withoutTenant()
            ->with(['certificates' => fn ($q) => $q->where('is_active', true)->whereNotNull('expiry_date')])
            ->get();

        foreach ($vendors as $vendor) {
            foreach ($vendor->certificates as $certificate) {
                $threshold = in_array($certificate->certificate_type->value, $gmpAndFoodSafety, true)
                    ? (int) config('compliance.thresholds.vendor_certificate_gmp_days')
                    : (int) config('compliance.thresholds.vendor_certificate_default_days');

                $daysRemaining = $this->daysRemaining($certificate->expiry_date);

                if ($daysRemaining > $threshold) {
                    continue;
                }

                ComplianceWarning::withoutTenant()->updateOrCreate(
                    [
                        'warnable_type' => 'vendor_certificate',
                        'warnable_id'   => $certificate->id,
                        'category'      => WarningCategory::VendorCertificateExpiry->value,
                    ],
                    [
                        'organization_id' => $vendor->organization_id,
                        'title'           => "{$certificate->certificate_type->label()} — {$vendor->name}",
                        'message'         => "{$certificate->certificate_type->label()} của nhà cung cấp \"{$vendor->name}\" sẽ hết hạn vào {$certificate->expiry_date->format('d/m/Y')}.",
                        'due_date'        => $certificate->expiry_date,
                        'severity'        => $this->severity($daysRemaining)->value,
                    ],
                );

                $activeIds[] = $certificate->id;
            }
        }

        return $activeIds;
    }

    /** @return string[] */
    private function scanBatches(): array
    {
        $activeIds = [];
        $threshold = (int) config('compliance.thresholds.batch_expiry_days');

        $batches = Batch::withoutTenant()
            ->where('status', BatchStatus::Available->value)
            ->where('current_qty', '>', 0)
            ->with(['product' => fn ($q) => $q->withoutTenant()])
            ->get();

        foreach ($batches as $batch) {
            $daysRemaining = $this->daysRemaining($batch->exp_date);

            if ($daysRemaining > $threshold) {
                continue;
            }

            ComplianceWarning::withoutTenant()->updateOrCreate(
                [
                    'warnable_type' => 'batch',
                    'warnable_id'   => $batch->id,
                    'category'      => WarningCategory::BatchNearExpiry->value,
                ],
                [
                    'organization_id' => $batch->organization_id,
                    'title'           => "Lô {$batch->internal_batch_code} — {$batch->product->name}",
                    'message'         => "Lô \"{$batch->internal_batch_code}\" của sản phẩm \"{$batch->product->name}\" (còn {$batch->current_qty} đơn vị) sẽ hết hạn vào {$batch->exp_date->format('d/m/Y')}.",
                    'due_date'        => $batch->exp_date,
                    'severity'        => $this->severity($daysRemaining)->value,
                ],
            );

            $activeIds[] = $batch->id;
        }

        return $activeIds;
    }

    /** @param string[] $activeIds */
    private function resolveStale(WarningCategory $category, array $activeIds): void
    {
        ComplianceWarning::withoutTenant()
            ->where('category', $category->value)
            ->whereNotIn('warnable_id', $activeIds ?: ['__none__'])
            ->whereIn('status', [WarningStatus::Pending->value, WarningStatus::Acknowledged->value])
            ->update(['status' => WarningStatus::Resolved->value, 'resolved_at' => now()]);
    }

    private function daysRemaining(\Illuminate\Support\Carbon $dueDate): int
    {
        $today = now()->startOfDay();

        return $dueDate->isPast() ? -$today->diffInDays($dueDate) : $today->diffInDays($dueDate);
    }

    private function severity(int $daysRemaining): WarningSeverity
    {
        return $daysRemaining <= (int) config('compliance.thresholds.critical_days')
            ? WarningSeverity::Critical
            : WarningSeverity::Warning;
    }
}
