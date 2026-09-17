<?php

namespace Modules\SalesPackage\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Customer\Models\Customer;
use Modules\SalesPackage\Data\Requests\StoreSalesPackageData;
use Modules\SalesPackage\Enums\SalesPackageStatus;
use Modules\SalesPackage\Models\SalesPackage;
use Modules\SalesPackage\Services\CustomerComplianceRuleEngine;

class StoreSalesPackageAction
{
    use AsAction;

    public function handle(StoreSalesPackageData $data, CustomerComplianceRuleEngine $ruleEngine): SalesPackage
    {
        $customer = Customer::query()->findOrFail($data->customer_id);

        $results   = $ruleEngine->evaluate($customer);
        $total     = count($results);
        $satisfied = count(array_filter($results, fn ($r) => $r->satisfied));
        $score     = $total > 0 ? (int) round($satisfied / $total * 100) : 0;

        return DB::transaction(function () use ($data, $customer, $score) {
            $nextVersion = (int) SalesPackage::query()->where('customer_id', $customer->id)->max('version') + 1;

            $package = SalesPackage::create([
                'customer_id'       => $customer->id,
                'name'              => $data->name,
                'expected_deadline' => $data->expected_deadline,
                'version'           => $nextVersion,
                'readiness_score'   => $score,
                'status'            => SalesPackageStatus::Draft->value,
                'notes'             => $data->notes,
                'created_by'        => auth()->id(),
            ]);

            $documents = ComplianceDocument::query()
                ->with('documentType')
                ->whereIn('id', $data->document_ids)
                ->get();

            foreach ($documents as $document) {
                $package->items()->create([
                    'compliance_document_id' => $document->id,
                    'document_group'         => $document->documentType->document_group->value,
                    'is_valid'               => $document->status === ComplianceDocumentStatus::Active && ! $document->isExpired(),
                ]);
            }

            foreach ($data->custom_documents as $custom) {
                $file = $custom['file'] ?? null;
                $name = $custom['name'] ?? null;
                if (! $file || ! $name) {
                    continue;
                }

                $item = $package->items()->create([
                    'compliance_document_id' => null,
                    'document_group'         => null,
                    'is_valid'               => true,
                    'is_custom'              => true,
                    'custom_name'            => $name,
                ]);

                $item->addMedia($file)->toMediaCollection('custom_document');
            }

            return $package;
        });
    }
}
