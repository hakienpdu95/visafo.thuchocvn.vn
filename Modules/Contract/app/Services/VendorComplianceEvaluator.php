<?php

namespace Modules\Contract\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Contract\Enums\ComplianceRequirementKind;
use Modules\Contract\Enums\ComplianceState;
use Modules\Contract\Enums\ContractStatus;
use Modules\Contract\Models\VendorComplianceRequirement;
use Modules\Vendor\Models\Vendor;

class VendorComplianceEvaluator
{
    private const STATE_RANK = ['ok' => 0, 'expiring' => 1, 'expired' => 2];

    /**
     * @param Collection<int, VendorComplianceRequirement> $requirements
     */
    public function evaluate(Vendor $vendor, Collection $requirements, ?int $warningDays = null): array
    {
        $today = Carbon::today();

        $items = $requirements
            ->groupBy(fn (VendorComplianceRequirement $r) => $r->groupIdentifier())
            ->map(function (Collection $group) use ($vendor, $today, $warningDays) {
                $head    = $group->first();
                $horizon = $today->copy()->addDays($warningDays ?? $head->warning_days);

                $best = $group
                    ->flatMap(fn (VendorComplianceRequirement $r) => $this->candidatesFor($vendor, $r))
                    ->map(fn (array $c) => $c + ['state' => $this->stateOf($c, $today, $horizon)])
                    ->sortBy([
                        fn ($a, $b) => self::STATE_RANK[$a['state']] <=> self::STATE_RANK[$b['state']],
                        fn ($a, $b) => ($b['until']?->timestamp ?? PHP_INT_MAX) <=> ($a['until']?->timestamp ?? PHP_INT_MAX),
                    ])
                    ->first();

                $state = $best === null ? ComplianceState::Missing : ComplianceState::from($best['state']);
                $until = $best['until'] ?? null;

                return [
                    'label'     => $head->label,
                    'kind'      => $head->kind->value,
                    'mandatory' => $head->is_mandatory,
                    'state'     => $state->value,
                    'ref'       => $best['ref'] ?? null,
                    'until'     => $until?->format('d/m/Y'),
                    'days_left' => $until !== null ? (int) $today->diffInDays($until, false) : null,
                    'url'       => $best['url'] ?? null,
                ];
            })
            ->values();

        $mandatory = $items->where('mandatory', true);
        $counts    = [
            'ok'       => $mandatory->where('state', ComplianceState::Ok->value)->count(),
            'expiring' => $mandatory->where('state', ComplianceState::Expiring->value)->count(),
            'expired'  => $mandatory->where('state', ComplianceState::Expired->value)->count(),
            'missing'  => $mandatory->where('state', ComplianceState::Missing->value)->count(),
            'total'    => $mandatory->count(),
        ];

        return [
            'id'                  => $vendor->id,
            'name'                => $vendor->name,
            'vendor_code'         => $vendor->vendor_code,
            'source_group'        => $vendor->source_group?->value,
            'source_group_label'  => $vendor->source_group?->label(),
            'vendor_status'       => $vendor->status?->value,
            'vendor_status_label' => $vendor->status?->label(),
            'items'               => $items->all(),
            'counts'              => $counts,
            'progress'            => $counts['total'] > 0 ? (int) round(($counts['ok'] + $counts['expiring']) / $counts['total'] * 100) : 100,
            'show_url'            => route('backend.vendors.show', ['vendor' => $vendor->id, 'tab' => 'contracts']),
        ];
    }

    private function candidatesFor(Vendor $vendor, VendorComplianceRequirement $requirement): Collection
    {
        if ($requirement->kind === ComplianceRequirementKind::Contract) {
            return $vendor->contracts
                ->where('contract_type_id', $requirement->contract_type_id)
                ->map(fn ($c) => [
                    'ref'     => $c->contract_number,
                    'until'   => $c->end_date,
                    'expired' => $c->status === ContractStatus::Expired,
                    'url'     => route('backend.contracts.show', $c->id),
                ]);
        }

        return $vendor->documents
            ->where('document_master_type_id', $requirement->document_master_type_id)
            ->map(fn ($d) => [
                'ref'     => $d->document_number,
                'until'   => $d->expiration_date,
                'expired' => $d->status === ComplianceDocumentStatus::Expired,
                'url'     => route('backend.vendors.show', ['vendor' => $vendor->id, 'tab' => 'documents']),
            ]);
    }

    private function stateOf(array $candidate, Carbon $today, Carbon $horizon): string
    {
        $until = $candidate['until'];

        return match (true) {
            $candidate['expired'], $until !== null && $until->lt($today) => ComplianceState::Expired->value,
            $until !== null && $until->lte($horizon)                     => ComplianceState::Expiring->value,
            default                                                      => ComplianceState::Ok->value,
        };
    }
}
