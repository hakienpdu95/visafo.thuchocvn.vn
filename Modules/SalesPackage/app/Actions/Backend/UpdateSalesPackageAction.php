<?php

namespace Modules\SalesPackage\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\SalesPackage\Data\Requests\UpdateSalesPackageData;
use Modules\SalesPackage\Models\SalesPackage;
use Modules\SalesPackage\Services\CustomerComplianceRuleEngine;

class UpdateSalesPackageAction
{
    use AsAction;

    public function handle(SalesPackage $package, UpdateSalesPackageData $data, CustomerComplianceRuleEngine $ruleEngine): SalesPackage
    {
        $customer  = $package->customer;
        $results   = $ruleEngine->evaluate($customer);
        $total     = count($results);
        $satisfied = count(array_filter($results, fn ($r) => $r->satisfied));
        $score     = $total > 0 ? (int) round($satisfied / $total * 100) : 0;

        return DB::transaction(function () use ($package, $data, $score) {
            $package->update([
                'name'              => $data->name,
                'expected_deadline' => $data->expected_deadline,
                'notes'             => $data->notes,
                'readiness_score'   => $score,
                'status'            => $data->status ?? $package->status->value,
            ]);

            $existingSystemItems = $package->items()->where('is_custom', false)->get();
            $keepIds             = $data->document_ids;

            foreach ($existingSystemItems as $item) {
                if (! in_array($item->compliance_document_id, $keepIds, true)) {
                    $item->delete();
                }
            }

            $alreadyPresent = $existingSystemItems->pluck('compliance_document_id')->all();
            $newDocumentIds = array_diff($keepIds, $alreadyPresent);

            if (! empty($newDocumentIds)) {
                $documents = ComplianceDocument::query()
                    ->with('documentType')
                    ->whereIn('id', $newDocumentIds)
                    ->get();

                foreach ($documents as $document) {
                    $package->items()->create([
                        'compliance_document_id' => $document->id,
                        'document_group'         => $document->documentType->document_group->value,
                        'is_valid'               => $document->status === ComplianceDocumentStatus::Active && ! $document->isExpired(),
                    ]);
                }
            }

            foreach ($data->custom_documents as $custom) {
                $files = $custom['files'] ?? [];
                $name  = $custom['name'] ?? null;
                if (empty($files) || ! $name) {
                    continue;
                }

                $item = $package->items()->create([
                    'compliance_document_id' => null,
                    'document_group'         => null,
                    'is_valid'               => true,
                    'is_custom'              => true,
                    'custom_name'            => $name,
                ]);

                foreach ($files as $file) {
                    $item->addMedia($file)->toMediaCollection('custom_document');
                }
            }

            return $package->fresh();
        });
    }
}
