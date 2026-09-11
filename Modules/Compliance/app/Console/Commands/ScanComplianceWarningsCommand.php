<?php

namespace Modules\Compliance\Console\Commands;

use Illuminate\Console\Command;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Enums\WarningCategory;
use Modules\Compliance\Enums\WarningSeverity;
use Modules\Compliance\Enums\WarningStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\ComplianceWarning;
use Modules\Employee\Models\Employee;

class ScanComplianceWarningsCommand extends Command
{
    protected $signature = 'compliance:scan-warnings';

    protected $description = 'Quét hồ sơ pháp lý sản phẩm, chứng chỉ nhà cung cấp, lô hàng cận date và hồ sơ y tế/ATTP nhân viên, cập nhật hộp thư cảnh báo compliance_warnings.';

    public function handle(): int
    {
        $expiredCount = $this->autoExpireDocuments();
        if ($expiredCount > 0) {
            $this->info("Đã chuyển {$expiredCount} hồ sơ pháp lý sang trạng thái hết hạn.");
        }

        $productIds = $this->scanProductCompliances();
        $this->resolveStale(WarningCategory::ProductComplianceExpiry, $productIds);

        $vendorIds = $this->scanVendorCertificates();
        $this->resolveStale(WarningCategory::VendorCertificateExpiry, $vendorIds);

        $this->resolveStale(WarningCategory::BatchNearExpiry, []);

        $employeeRecordIds = $this->scanEmployeeHealthRecords();
        $this->resolveStale(WarningCategory::EmployeeHealthRecordExpiry, $employeeRecordIds);

        $internalFacilityIds = $this->scanInternalFacilityDocuments();
        $this->resolveStale(WarningCategory::InternalFacilityComplianceExpiry, $internalFacilityIds);

        $total = count($productIds) + count($vendorIds) + count($employeeRecordIds) + count($internalFacilityIds);
        $this->info("Đã cập nhật {$total} cảnh báo đang hiệu lực.");

        return self::SUCCESS;
    }

    private function autoExpireDocuments(): int
    {
        return ComplianceDocument::query()
            ->where('status', ComplianceDocumentStatus::Active->value)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now()->toDateString())
            ->update(['status' => ComplianceDocumentStatus::Expired->value]);
    }

    /** @return string[] */
    private function scanProductCompliances(): array
    {
        return $this->scanDocumentsFor(
            documentableType: 'product',
            category: WarningCategory::ProductComplianceExpiry,
            threshold: (int) config('compliance.thresholds.product_compliance_default_days'),
            titleFor: fn ($document, $product) => "{$document->documentType->name} — {$product->name}",
            messageFor: fn ($document, $product) => "Hồ sơ \"{$document->documentType->name}\" của sản phẩm \"{$product->name}\" (SKU {$product->sku}) sẽ hết hạn vào {$document->expiration_date->format('d/m/Y')}.",
        );
    }

    /** @return string[] */
    private function scanVendorCertificates(): array
    {
        $gmpAndFoodSafety = ['supplier_gmp', 'supplier_attp'];

        return $this->scanDocumentsFor(
            documentableType: 'vendor',
            category: WarningCategory::VendorCertificateExpiry,
            threshold: fn ($document) => in_array($document->documentType->code, $gmpAndFoodSafety, true)
                ? (int) config('compliance.thresholds.vendor_certificate_gmp_days')
                : (int) config('compliance.thresholds.vendor_certificate_default_days'),
            titleFor: fn ($document, $vendor) => "{$document->documentType->name} — {$vendor->name}",
            messageFor: fn ($document, $vendor) => "{$document->documentType->name} của nhà cung cấp \"{$vendor->name}\" sẽ hết hạn vào {$document->expiration_date->format('d/m/Y')}.",
        );
    }

    /** @return string[] */
    private function scanInternalFacilityDocuments(): array
    {
        return $this->scanDocumentsFor(
            documentableType: 'internal_facility',
            category: WarningCategory::InternalFacilityComplianceExpiry,
            threshold: (int) config('compliance.thresholds.internal_facility_default_days'),
            titleFor: fn ($document, $facility) => "{$document->documentType->name} — {$facility->name}",
            messageFor: fn ($document, $facility) => "{$document->documentType->name} của cơ sở \"{$facility->name}\" sẽ hết hạn vào {$document->expiration_date->format('d/m/Y')}.",
        );
    }

    /**
     * @param int|(callable(ComplianceDocument): int) $threshold
     * @param callable(ComplianceDocument, mixed): string $titleFor
     * @param callable(ComplianceDocument, mixed): string $messageFor
     * @return string[]
     */
    private function scanDocumentsFor(string $documentableType, WarningCategory $category, int|callable $threshold, callable $titleFor, callable $messageFor): array
    {
        $activeIds = [];

        $documents = ComplianceDocument::query()
            ->where('documentable_type', $documentableType)
            ->where('status', ComplianceDocumentStatus::Active->value)
            ->whereNotNull('expiration_date')
            ->with(['documentType', 'documentable' => fn ($q) => $q->withoutTenant()])
            ->get();

        foreach ($documents as $document) {
            $owner = $document->documentable;
            if ($owner === null) {
                continue;
            }

            $days = is_callable($threshold) ? $threshold($document) : $threshold;
            $daysRemaining = $this->daysRemaining($document->expiration_date);

            if ($daysRemaining > $days) {
                continue;
            }

            ComplianceWarning::withoutTenant()->updateOrCreate(
                [
                    'warnable_type' => 'compliance_document',
                    'warnable_id'   => $document->id,
                    'category'      => $category->value,
                ],
                [
                    'title'    => $titleFor($document, $owner),
                    'message'  => $messageFor($document, $owner),
                    'due_date' => $document->expiration_date,
                    'severity' => $this->severity($daysRemaining)->value,
                ],
            );

            $activeIds[] = $document->id;
        }

        return $activeIds;
    }

    /** @return string[] */
    private function scanEmployeeHealthRecords(): array
    {
        $activeIds = [];
        $threshold = (int) config('compliance.thresholds.employee_health_record_days');
        $critical  = (int) config('compliance.thresholds.employee_health_record_critical_days');

        $employees = Employee::query()
            ->with(['latestHealthCheck', 'latestAttpTraining'])
            ->get();

        foreach ($employees as $employee) {
            foreach (['latestHealthCheck', 'latestAttpTraining'] as $relation) {
                $record = $employee->{$relation};

                if ($record === null || $record->expiry_date === null) {
                    continue;
                }

                $daysRemaining = $this->daysRemaining($record->expiry_date);

                if ($daysRemaining > $threshold) {
                    continue;
                }

                ComplianceWarning::withoutTenant()->updateOrCreate(
                    [
                        'warnable_type' => 'employee_health_record',
                        'warnable_id'   => $record->id,
                        'category'      => WarningCategory::EmployeeHealthRecordExpiry->value,
                    ],
                    [
                        'title'           => "{$record->record_type->label()} — {$employee->full_name}",
                        'message'         => "{$record->record_type->label()} của nhân viên \"{$employee->full_name}\" sẽ hết hạn vào {$record->expiry_date->format('d/m/Y')}.",
                        'due_date'        => $record->expiry_date,
                        'severity'        => $this->severity($daysRemaining, $critical)->value,
                    ],
                );

                $activeIds[] = $record->id;
            }
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

    private function severity(int $daysRemaining, ?int $criticalDays = null): WarningSeverity
    {
        $threshold = $criticalDays ?? (int) config('compliance.thresholds.critical_days');

        return $daysRemaining <= $threshold
            ? WarningSeverity::Critical
            : WarningSeverity::Warning;
    }
}
