<?php

namespace Modules\SalesPackage\Services;

use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\InternalFacility;
use Modules\Customer\Enums\MealModel;
use Modules\Customer\Models\Customer;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Services\ComplianceRequirement;
use Modules\Product\Services\ComplianceRequirementResult;
use Modules\Product\Services\ProductComplianceRuleEngine;

class CustomerComplianceRuleEngine
{
    public function __construct(
        private readonly ProductComplianceRuleEngine $productRuleEngine,
    ) {}

    /**
     * @return ComplianceRequirementResult[]
     */
    public function evaluate(Customer $customer): array
    {
        $results = $this->internalRequirements($customer);

        $products = $customer->relationLoaded('products')
            ? $customer->products
            : $customer->products()->with(['partnerProducts.vendor', 'partnerProducts.documents.documentType'])->get();

        foreach ($products as $product) {
            $partnerProducts = $product->relationLoaded('partnerProducts')
                ? $product->partnerProducts
                : $product->partnerProducts()->with(['vendor', 'documents.documentType'])->get();

            foreach ($partnerProducts as $partnerProduct) {
                foreach ($this->productRuleEngine->evaluate($partnerProduct) as $result) {
                    $label = $result->requirement->label . ' — ' . $partnerProduct->name;
                    $results[] = new ComplianceRequirementResult(
                        new ComplianceRequirement($label, $result->requirement->documentTypeCodes, $result->requirement->legalBasis),
                        $result->satisfied,
                        $result->matchedCompliance,
                    );
                }
            }
        }

        return $results;
    }

    /**
     * @return ComplianceRequirementResult[]
     */
    private function internalRequirements(Customer $customer): array
    {
        $requiredGroups = [DocumentGroupType::LegalFacility->value, DocumentGroupType::AttpQuality->value];

        if ($customer->meal_model !== MealModel::IngredientSupply) {
            $requiredGroups[] = DocumentGroupType::Personnel->value;
        }

        $docTypes = DocumentMasterType::query()
            ->applicableTo('internal')
            ->whereIn('document_group', $requiredGroups)
            ->orderBy('name')
            ->get();

        $facility = InternalFacility::query()
            ->where('type', 'headquarter')
            ->with(['documents.documentType'])
            ->first();

        $documents = $facility?->documents ?? collect();

        return $docTypes->map(function (DocumentMasterType $docType) use ($documents) {
            $requirement = new ComplianceRequirement($docType->name, [$docType->code]);

            /** @var ComplianceDocument|null $match */
            $match = $documents->first(
                fn (ComplianceDocument $d) => $d->document_master_type_id === $docType->id
                    && $d->status === ComplianceDocumentStatus::Active
                    && ! $d->isExpired(),
            );

            return new ComplianceRequirementResult($requirement, $match !== null, $match);
        })->all();
    }
}
