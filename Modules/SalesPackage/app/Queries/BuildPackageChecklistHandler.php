<?php

namespace Modules\SalesPackage\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Services\ComplianceRequirementResult;
use Modules\SalesPackage\Services\CustomerComplianceRuleEngine;

class BuildPackageChecklistHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly CustomerComplianceRuleEngine $ruleEngine,
    ) {}

    public function handle(QueryInterface $query): array
    {
        /** @var BuildPackageChecklistQuery $query */
        $customer = $query->customer;

        $customer->loadMissing([
            'products.partnerProducts.vendor',
            'products.partnerProducts.documents.documentType',
        ]);

        $results = $this->ruleEngine->evaluate($customer);

        $codes = collect($results)
            ->flatMap(fn (ComplianceRequirementResult $r) => $r->requirement->documentTypeCodes)
            ->unique()
            ->values()
            ->all();

        $groupByCode = DocumentMasterType::query()
            ->whereIn('code', $codes)
            ->get(['code', 'document_group'])
            ->keyBy('code');

        $items = collect($results)->map(function (ComplianceRequirementResult $r) use ($groupByCode) {
            $document = $r->matchedCompliance;
            $document?->loadMissing('documentType');

            $fallbackType = $groupByCode->get($r->requirement->documentTypeCodes[0] ?? null);
            $documentGroupEnum = $document?->documentType?->document_group
                ?? $fallbackType?->document_group;

            return [
                'label'                  => $r->requirement->label,
                'satisfied'              => $r->satisfied,
                'required'               => true,
                'document_group'         => $documentGroupEnum?->value,
                'document_group_label'   => $documentGroupEnum?->label() ?? 'Khác',
                'compliance_document_id' => $document?->id,
                'is_expiring_soon'       => $document?->isExpiringWithinDays(30) ?? false,
                'note'                   => match (true) {
                    $document === null                     => 'Chưa có hồ sơ',
                    $document->expiration_date !== null     => 'Hiệu lực đến ' . $document->expiration_date->format('d/m/Y'),
                    default                                  => 'Đã có hồ sơ',
                },
            ];
        })->values()->all();

        $total     = count($items);
        $satisfied = collect($items)->where('satisfied', true)->count();
        $score     = $total > 0 ? (int) round($satisfied / $total * 100) : 0;

        return [
            'customer'          => $customer,
            'expected_deadline' => $customer->expected_deadline?->format('Y-m-d'),
            'items'             => $items,
            'total'             => $total,
            'satisfied_count'   => $satisfied,
            'score'             => $score,
        ];
    }
}
