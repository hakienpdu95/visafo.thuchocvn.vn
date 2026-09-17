<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Services\ReadinessScoringService;
use Modules\SalesPackage\Enums\SalesPackageStatus;
use Modules\SalesPackage\Models\SalesPackage;
use Modules\Vendor\Enums\VendorStatus;
use Modules\Vendor\Models\Vendor;

class DashboardService
{
    public function __construct(
        private readonly ReadinessScoringService $readinessScoringService,
    ) {}

    public function getData(User $user): array
    {
        $primaryRole = $user->getRoleNames()->first() ?? '';
        $readiness   = $this->readinessScoringService->score();

        return [
            'primary_role'     => $primaryRole,
            'readiness'        => $readiness,
            'priority_issues'  => array_slice($readiness->topIssues, 0, 4),
            'compliance_stats' => $this->complianceStats(),
            'vendor_stats'     => $this->vendorStats(),
            'package_stats'    => $this->packageStats(),
            'recent_activity'  => $this->recentActivity(),
        ];
    }

    private function complianceStats(): array
    {
        $total = ComplianceDocument::count();
        $valid = ComplianceDocument::where('status', ComplianceDocumentStatus::Active->value)->count();

        return [
            'total'   => $total,
            'valid'   => $valid,
            'percent' => $total > 0 ? (int) round($valid / $total * 100) : 0,
        ];
    }

    private function vendorStats(): array
    {
        $activeCount = Vendor::query()->where('status', VendorStatus::Active->value)->count();

        $supplyingVendors = Vendor::query()
            ->where('status', VendorStatus::Active->value)
            ->whereHas('partnerProducts')
            ->with(['activeDocuments.documentType'])
            ->get();

        $identityCodes = ['supplier_business_registration', 'personal_id_card'];
        $missingCount  = 0;

        foreach ($supplyingVendors as $vendor) {
            $codes = $vendor->activeDocuments
                ->filter(fn ($d) => ! $d->isExpired())
                ->map(fn ($d) => $d->documentType?->code)
                ->filter()
                ->all();

            $hasIdentity   = array_intersect($identityCodes, $codes) !== [];
            $hasAttp       = in_array('supplier_attp', $codes, true);
            $hasCommitment = in_array('supplier_commitment', $codes, true);

            if (! ($hasIdentity && $hasAttp && $hasCommitment)) {
                $missingCount++;
            }
        }

        return [
            'active_count'  => $activeCount,
            'missing_count' => $missingCount,
        ];
    }

    private function packageStats(): array
    {
        return [
            'total' => SalesPackage::count(),
            'draft' => SalesPackage::where('status', SalesPackageStatus::Draft->value)->count(),
        ];
    }

    /**
     * @return Collection<int, array{type: string, title: string, subject: string, status_label: string, status_badge: string, url: string, updated_at: Carbon, time_label: string}>
     */
    private function recentActivity(): Collection
    {
        $documents = ComplianceDocument::query()
            ->with(['documentType', 'documentable'])
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get()
            ->map(fn (ComplianceDocument $document) => [
                'type'         => 'document',
                'title'        => $document->documentType?->name ?? $document->custom_name ?? 'Tài liệu',
                'subject'      => $this->documentSubject($document),
                'status_label' => $document->status->label(),
                'status_badge' => $document->status->badgeClass(),
                'url'          => route('backend.document-repository.index'),
                'updated_at'   => $document->updated_at,
            ]);

        $packages = SalesPackage::query()
            ->with('customer')
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get()
            ->map(fn (SalesPackage $package) => [
                'type'         => 'package',
                'title'        => $package->name,
                'subject'      => $package->customer?->name ?? '—',
                'status_label' => $package->status->label(),
                'status_badge' => $package->status->badgeClass(),
                'url'          => route('backend.sales-packages.show', $package),
                'updated_at'   => $package->updated_at,
            ]);

        return $documents->concat($packages)
            ->sortByDesc('updated_at')
            ->take(15)
            ->map(fn (array $row) => $row + ['time_label' => $this->formatTime($row['updated_at'])])
            ->values();
    }

    private function documentSubject(ComplianceDocument $document): string
    {
        return match ($document->documentable_type) {
            'vendor'                       => $document->documentable?->name ?? 'Nhà cung cấp',
            'internal_facility'            => 'Hồ sơ DN',
            'product', 'partner_product'   => $document->documentable?->name ?? 'Sản phẩm',
            default                        => 'Nội bộ dùng chung',
        };
    }

    private function formatTime(Carbon $dt): string
    {
        if ($dt->isToday()) {
            return 'Hôm nay, ' . $dt->format('H:i');
        }

        if ($dt->isYesterday()) {
            return 'Hôm qua, ' . $dt->format('H:i');
        }

        return $dt->format('d/m/Y');
    }
}
