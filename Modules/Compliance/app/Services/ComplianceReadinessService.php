<?php

namespace Modules\Compliance\Services;

use Illuminate\Support\Collection;
use Modules\Compliance\Enums\ReadinessStatus;
use Modules\Product\Services\ProductComplianceRuleEngine;
use Modules\Vendor\Models\Vendor;

class ComplianceReadinessService
{
    public function __construct(
        private readonly ProductComplianceRuleEngine $ruleEngine,
    ) {}

    /**
     * @return Collection<int, VendorReadinessResult>
     */
    public function evaluateAll(): Collection
    {
        $vendors = Vendor::query()
            ->whereHas('partnerProducts')
            ->with([
                'partnerProducts.product.category',
                'partnerProducts.activeDocuments.documentType',
            ])
            ->orderBy('name')
            ->get();

        return $vendors->map(fn (Vendor $vendor) => $this->evaluateVendor($vendor));
    }

    public function evaluateVendor(Vendor $vendor): VendorReadinessResult
    {
        $partnerProducts = $vendor->relationLoaded('partnerProducts')
            ? $vendor->partnerProducts
            : $vendor->partnerProducts()->with(['product.category', 'activeDocuments.documentType'])->get();

        $hasMissing      = false;
        $hasExpiringSoon = false;
        $missingByProduct = [];

        foreach ($partnerProducts as $partnerProduct) {
            $results = $this->ruleEngine->evaluate($partnerProduct);
            $missing = $this->ruleEngine->missing($results);

            if (!empty($missing)) {
                $hasMissing = true;
                $missingByProduct[] = [
                    'partnerProduct' => $partnerProduct,
                    'missing'        => $missing,
                ];
            }

            foreach ($results as $result) {
                if ($result->satisfied && $result->matchedCompliance?->isExpiringWithinDays(30)) {
                    $hasExpiringSoon = true;
                }
            }
        }

        $status = match (true) {
            $hasMissing      => ReadinessStatus::NotReady,
            $hasExpiringSoon => ReadinessStatus::Warning,
            default          => ReadinessStatus::Ready,
        };

        return new VendorReadinessResult($vendor, $status, $missingByProduct, $partnerProducts->count());
    }
}
