<?php

namespace Modules\Compliance\Services;

use Illuminate\Support\Collection;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\InternalFacility;
use Modules\Compliance\Services\Readiness\ReadinessCategoryResult;
use Modules\Compliance\Services\Readiness\ReadinessChecklistItem;
use Modules\Compliance\Services\Readiness\ReadinessScoreResult;
use Modules\Employee\Enums\RecordStatus;
use Modules\Employee\Models\Employee;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Models\FarmingBatch;
use Modules\Vendor\Enums\VendorStatus;
use Modules\Vendor\Models\Vendor;

class ReadinessScoringService
{
    private const LEGAL_MAX = 20;

    private const ATTP_MAX = 25;

    private const SUPPLY_CHAIN_MAX = 25;

    private const SUPPLY_CHAIN_VENDOR_MAX = 13;

    private const SUPPLY_CHAIN_FARMING_MAX = 12;

    private const HR_MAX = 15;

    private const HR_HEALTH_MAX = 7;

    private const HR_ATTP_MAX = 8;

    private const COMMERCIAL_MAX = 15;

    public function score(): ReadinessScoreResult
    {
        $legal      = $this->scoreLegal();
        $attp       = $this->scoreAttp();
        $supply     = $this->scoreSupplyChain();
        $hr         = $this->scoreHr();
        $commercial = $this->scoreCommercial();

        $categories = [$legal, $attp, $supply, $hr, $commercial];

        $totalScore = array_sum(array_map(fn (ReadinessCategoryResult $c) => $c->score, $categories));
        $maxScore   = array_sum(array_map(fn (ReadinessCategoryResult $c) => $c->maxScore, $categories));

        $topIssues = collect($categories)
            ->flatMap(fn (ReadinessCategoryResult $category) => collect($category->items)
                ->filter(fn (ReadinessChecklistItem $item) => ! $item->passed && $item->pointsLost > 0)
                ->map(fn (ReadinessChecklistItem $item) => [
                    'category'    => $category->label,
                    'label'       => $item->label,
                    'note'        => $item->note,
                    'pointsLost'  => $item->pointsLost,
                ]))
            ->sortByDesc('pointsLost')
            ->take(5)
            ->values()
            ->all();

        return new ReadinessScoreResult($totalScore, $maxScore, $categories, $topIssues);
    }

    private function scoreLegal(): ReadinessCategoryResult
    {
        $facility = InternalFacility::query()
            ->where('type', 'headquarter')
            ->with(['documents' => fn ($q) => $q->with('documentType')])
            ->first();

        $docTypes = DocumentMasterType::query()
            ->applicableTo('internal')
            ->where('document_group', DocumentGroupType::LegalFacility->value)
            ->orderBy('name')
            ->get();

        [$score, $items] = $this->evaluateFacilityChecklist($facility, $docTypes, self::LEGAL_MAX);

        return new ReadinessCategoryResult('legal', 'Pháp lý doanh nghiệp', $score, self::LEGAL_MAX, $items);
    }

    private function scoreAttp(): ReadinessCategoryResult
    {
        $facility = InternalFacility::query()
            ->where('type', 'headquarter')
            ->with(['documents' => fn ($q) => $q->with('documentType')])
            ->first();

        $docTypes = DocumentMasterType::query()
            ->applicableTo('internal')
            ->whereIn('document_group', [DocumentGroupType::Traceability->value, DocumentGroupType::MonitoringLogs->value])
            ->orderBy('name')
            ->get();

        [$score, $items] = $this->evaluateFacilityChecklist($facility, $docTypes, self::ATTP_MAX);

        return new ReadinessCategoryResult('attp', 'An toàn thực phẩm', $score, self::ATTP_MAX, $items);
    }

    /**
     * @param Collection<int, DocumentMasterType> $docTypes
     * @return array{0: int, 1: ReadinessChecklistItem[]}
     */
    private function evaluateFacilityChecklist(?InternalFacility $facility, Collection $docTypes, int $maxPoints): array
    {
        if ($docTypes->isEmpty()) {
            return [$maxPoints, []];
        }

        if ($facility === null) {
            return [0, [new ReadinessChecklistItem('Hồ sơ Trụ sở chính', false, 'Chưa khởi tạo hồ sơ Trụ sở chính', $maxPoints)]];
        }

        $perItem = $maxPoints / $docTypes->count();
        $earned  = 0.0;
        $items   = [];

        foreach ($docTypes as $docType) {
            $document = $facility->documents
                ->where('document_master_type_id', $docType->id)
                ->where('status', ComplianceDocumentStatus::Active)
                ->sortByDesc('issue_date')
                ->first();

            if ($document === null) {
                $items[] = new ReadinessChecklistItem($docType->name, false, 'Chưa có hồ sơ', $perItem);
                continue;
            }

            if ($document->isExpired()) {
                $items[] = new ReadinessChecklistItem($docType->name, false, 'Đã hết hạn ngày ' . $document->expiration_date->format('d/m/Y'), $perItem);
                continue;
            }

            if ($document->isExpiringWithinDays(30)) {
                $earned += $perItem * 0.5;
                $items[] = new ReadinessChecklistItem($docType->name, true, 'Sắp hết hạn — hiệu lực đến ' . $document->expiration_date->format('d/m/Y'), $perItem * 0.5);
                continue;
            }

            $earned += $perItem;
            $note    = $document->expiration_date !== null
                ? 'Hiệu lực đến ' . $document->expiration_date->format('d/m/Y')
                : 'Đã có hồ sơ';
            $items[] = new ReadinessChecklistItem($docType->name, true, $note, 0);
        }

        return [(int) round($earned), $items];
    }

    private function scoreSupplyChain(): ReadinessCategoryResult
    {
        $items = [];

        $vendors = Vendor::query()
            ->where('status', VendorStatus::Active->value)
            ->whereHas('partnerProducts')
            ->with(['activeDocuments.documentType'])
            ->orderBy('name')
            ->get();

        $identityCodes   = ['supplier_business_registration', 'personal_id_card'];
        $attpCode        = 'supplier_attp';
        $commitmentCode  = 'supplier_commitment';

        $vendorRatioSum   = 0.0;
        $worstVendorName  = null;
        $worstVendorRatio = 1.0;
        $worstVendorMissing = [];

        foreach ($vendors as $vendor) {
            $codes = $vendor->activeDocuments
                ->filter(fn ($d) => ! $d->isExpired())
                ->map(fn ($d) => $d->documentType?->code)
                ->filter()
                ->all();

            $missing = [];
            $satisfied = 0;

            if (array_intersect($identityCodes, $codes) !== []) {
                $satisfied++;
            } else {
                $missing[] = 'hồ sơ định danh';
            }

            if (in_array($attpCode, $codes, true)) {
                $satisfied++;
            } else {
                $missing[] = 'giấy chứng nhận ATTP';
            }

            if (in_array($commitmentCode, $codes, true)) {
                $satisfied++;
            } else {
                $missing[] = 'bản cam kết an toàn thực phẩm';
            }

            $ratio = $satisfied / 3;
            $vendorRatioSum += $ratio;

            if ($ratio < $worstVendorRatio) {
                $worstVendorRatio  = $ratio;
                $worstVendorName   = $vendor->name;
                $worstVendorMissing = $missing;
            }
        }

        $vendorCount    = $vendors->count();
        $vendorAvgRatio = $vendorCount > 0 ? $vendorRatioSum / $vendorCount : 0.0;
        $vendorScore    = $vendorCount > 0 ? (int) round($vendorAvgRatio * self::SUPPLY_CHAIN_VENDOR_MAX) : 0;
        $vendorReadyCount = (int) round($vendorAvgRatio * $vendorCount);

        $items[] = new ReadinessChecklistItem(
            'Hồ sơ định danh & ATTP nhà cung cấp',
            $vendorCount > 0 && $vendorAvgRatio >= 1.0,
            $vendorCount > 0
                ? "{$vendorReadyCount}/{$vendorCount} nhà cung cấp đầy đủ hồ sơ"
                : 'Thiếu dữ liệu — chưa có nhà cung cấp nào đang hợp tác',
            $vendorCount > 0 ? (1 - $vendorAvgRatio) * self::SUPPLY_CHAIN_VENDOR_MAX : self::SUPPLY_CHAIN_VENDOR_MAX,
        );

        if ($worstVendorName !== null && $worstVendorRatio < 1.0) {
            $items[] = new ReadinessChecklistItem(
                "NCC {$worstVendorName}",
                false,
                'Thiếu ' . implode(', ', $worstVendorMissing),
                0,
            );
        }

        $batches = FarmingBatch::query()
            ->where('status', '!=', 'cancelled')
            ->get(['id', 'pre_harvest_status', 'batch_code']);

        $batchTotal  = $batches->count();
        $batchPassed = $batches->where('pre_harvest_status', 'passed')->count();
        $batchRatio  = $batchTotal > 0 ? $batchPassed / $batchTotal : 0.0;
        $batchScore  = $batchTotal > 0 ? (int) round($batchRatio * self::SUPPLY_CHAIN_FARMING_MAX) : 0;

        $items[] = new ReadinessChecklistItem(
            'Nhật ký sản xuất trước thu hoạch',
            $batchTotal > 0 && $batchRatio >= 1.0,
            $batchTotal > 0
                ? "{$batchPassed}/{$batchTotal} lô sản xuất đạt yêu cầu trước thu hoạch"
                : 'Thiếu dữ liệu — chưa có lô sản xuất nào được ghi nhận',
            $batchTotal > 0 ? (1 - $batchRatio) * self::SUPPLY_CHAIN_FARMING_MAX : self::SUPPLY_CHAIN_FARMING_MAX,
        );

        $blockedBatch = $batches->firstWhere('pre_harvest_status', 'blocked');
        if ($blockedBatch !== null) {
            $items[] = new ReadinessChecklistItem(
                "Lô {$blockedBatch->batch_code}",
                false,
                'Chưa được QC phê duyệt cho phép thu hoạch',
                0,
            );
        }

        $score = min($vendorScore + $batchScore, self::SUPPLY_CHAIN_MAX);

        return new ReadinessCategoryResult('supply_chain', 'Nguồn cung & Truy xuất', $score, self::SUPPLY_CHAIN_MAX, $items);
    }

    private function scoreHr(): ReadinessCategoryResult
    {
        $employees = Employee::query()
            ->with(['latestHealthCheck', 'latestAttpTraining'])
            ->get();

        $total = $employees->count();

        if ($total === 0) {
            return new ReadinessCategoryResult('hr', 'Nhân sự & sức khỏe', 0, self::HR_MAX, [
                new ReadinessChecklistItem('Hồ sơ nhân sự', false, 'Thiếu dữ liệu nhân sự nền tảng — chưa có nhân sự nào được ghi nhận', self::HR_MAX),
            ]);
        }

        $healthValid = 0;
        $attpValid   = 0;
        $worstEmployee = null;
        $worstSeverity = -1;

        foreach ($employees as $employee) {
            $healthStatus = $employee->healthCheckStatus();
            $attpStatus   = $employee->attpTrainingStatus();

            if ($healthStatus === RecordStatus::Valid) {
                $healthValid++;
            }
            if ($attpStatus === RecordStatus::Valid) {
                $attpValid++;
            }

            $severity = max(
                $healthStatus === RecordStatus::Valid ? 0 : 1,
                $attpStatus === RecordStatus::Valid ? 0 : 1,
            );

            if ($severity > 0 && $severity > $worstSeverity) {
                $worstSeverity = $severity;
                $worstEmployee = [
                    'name'   => $employee->full_name,
                    'health' => $healthStatus,
                    'attp'   => $attpStatus,
                ];
            }
        }

        $healthRatio = $healthValid / $total;
        $attpRatio   = $attpValid / $total;

        $healthScore = (int) round($healthRatio * self::HR_HEALTH_MAX);
        $attpScore   = (int) round($attpRatio * self::HR_ATTP_MAX);

        $items = [
            new ReadinessChecklistItem(
                'Khám sức khỏe định kỳ',
                $healthRatio >= 1.0,
                "{$healthValid}/{$total} nhân sự còn hạn",
                (1 - $healthRatio) * self::HR_HEALTH_MAX,
            ),
            new ReadinessChecklistItem(
                'Tập huấn kiến thức ATTP',
                $attpRatio >= 1.0,
                "{$attpValid}/{$total} nhân sự còn hạn",
                (1 - $attpRatio) * self::HR_ATTP_MAX,
            ),
        ];

        if ($worstEmployee !== null) {
            $reasons = [];
            if ($worstEmployee['health'] !== RecordStatus::Valid) {
                $reasons[] = 'khám sức khỏe ' . $worstEmployee['health']->label();
            }
            if ($worstEmployee['attp'] !== RecordStatus::Valid) {
                $reasons[] = 'tập huấn ATTP ' . $worstEmployee['attp']->label();
            }

            $items[] = new ReadinessChecklistItem(
                "NV {$worstEmployee['name']}",
                false,
                ucfirst(implode(', ', $reasons)),
                0,
            );
        }

        return new ReadinessCategoryResult('hr', 'Nhân sự & sức khỏe', min($healthScore + $attpScore, self::HR_MAX), self::HR_MAX, $items);
    }

    private function scoreCommercial(): ReadinessCategoryResult
    {
        $facility = InternalFacility::query()
            ->where('type', 'headquarter')
            ->with(['documents' => fn ($q) => $q->with('documentType')])
            ->first();

        $docTypes = DocumentMasterType::query()
            ->applicableTo('internal')
            ->where('document_group', DocumentGroupType::Commercial->value)
            ->orderBy('name')
            ->get();

        [$score, $items] = $this->evaluateFacilityChecklist($facility, $docTypes, self::COMMERCIAL_MAX);

        return new ReadinessCategoryResult('commercial', 'Năng lực thương mại', $score, self::COMMERCIAL_MAX, $items);
    }
}
