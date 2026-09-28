<?php

namespace Modules\Contract\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Support\Permissions\ModuleAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Contract\Enums\ContractPartyType;
use Modules\Contract\Enums\ContractStatus;
use Modules\Contract\Models\Contract;
use Modules\Contract\Models\VendorComplianceRequirement;
use Modules\Contract\Services\VendorComplianceEvaluator;
use Modules\Contract\Support\VendorComplianceCache;
use Modules\Vendor\Models\Vendor;

class VendorComplianceMatrixHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly VendorComplianceEvaluator $evaluator,
    ) {}

    /**
     * @return array{paginator: LengthAwarePaginator, summary: array<string, int>}
     */
    public function handle(QueryInterface $query): array
    {
        /** @var VendorComplianceMatrixQuery $query */
        $rows = collect(Cache::remember(
            VendorComplianceCache::key($this->scopeKey() . ':d' . $query->warningDays),
            VendorComplianceCache::TTL_SECONDS,
            fn () => $this->buildAll($query->warningDays),
        ));

        $scoped = $rows
            ->when($query->vendorStatus, fn (Collection $c, $s) => $c->where('vendor_status', $s))
            ->when($query->sourceGroup, fn (Collection $c, $g) => $c->where('source_group', $g))
            ->when($query->search, function (Collection $c, $term) {
                $needle = Str::lower(Str::ascii($term));

                return $c->filter(fn ($r) => Str::contains(Str::lower(Str::ascii($r['name'] . ' ' . $r['vendor_code'])), $needle));
            });

        $summary = [
            'total'    => $scoped->count(),
            'complete' => $scoped->filter(fn ($r) => $this->matchesState($r, 'complete'))->count(),
            'missing'  => $scoped->filter(fn ($r) => $this->matchesState($r, 'missing'))->count(),
            'expiring' => $scoped->filter(fn ($r) => $this->matchesState($r, 'expiring'))->count(),
            'expired'  => $scoped->filter(fn ($r) => $this->matchesState($r, 'expired'))->count(),
        ];

        $filtered = $scoped
            ->when($query->state, fn (Collection $c, $s) => $c->filter(fn ($r) => $this->matchesState($r, $s)))
            ->sortBy($this->sorter($query->sortField), SORT_REGULAR, $query->sortDir === 'desc')
            ->values();

        $paginator = new LengthAwarePaginator(
            $filtered->forPage($query->page, $query->perPage)->values(),
            $filtered->count(),
            $query->perPage,
            $query->page,
        );

        return ['paginator' => $paginator, 'summary' => $summary];
    }

    private function buildAll(int $warningDays): array
    {
        $requirements = VendorComplianceRequirement::query()->get();
        $byGroup      = $requirements->groupBy(fn ($r) => $r->source_group?->value ?? '*');
        $common       = $byGroup->get('*', collect());

        $contractTypeIds = $requirements->pluck('contract_type_id')->filter()->unique()->values();
        $documentTypeIds = $requirements->pluck('document_master_type_id')->filter()->unique()->values();

        return Vendor::query()
            ->visibleTo()
            ->select(['id', 'name', 'vendor_code', 'source_group', 'status'])
            ->with([
                'contracts' => fn ($q) => $q
                    ->visibleTo()
                    ->select(['id', 'vendor_id', 'contract_type_id', 'contract_number', 'end_date', 'status'])
                    ->where('type', ContractPartyType::Input->value)
                    ->where('status', '!=', ContractStatus::Terminated->value)
                    ->whereIn('contract_type_id', $contractTypeIds),
                'documents' => fn ($q) => $q
                    ->select(['id', 'documentable_type', 'documentable_id', 'document_master_type_id', 'document_number', 'expiration_date', 'status'])
                    ->whereIn('status', [ComplianceDocumentStatus::Active->value, ComplianceDocumentStatus::Expired->value])
                    ->whereIn('document_master_type_id', $documentTypeIds),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Vendor $vendor) => $this->evaluator->evaluate(
                $vendor,
                $common->merge($byGroup->get($vendor->source_group?->value, collect())),
                $warningDays,
            ))
            ->all();
    }

    private function matchesState(array $row, string $state): bool
    {
        $c = $row['counts'];

        return match ($state) {
            'complete' => $c['missing'] === 0 && $c['expired'] === 0,
            'missing'  => $c['missing'] > 0,
            'expiring' => $c['expiring'] > 0,
            'expired'  => $c['expired'] > 0,
            default    => true,
        };
    }

    private function sorter(string $field): callable
    {
        return match ($field) {
            'name'     => fn ($r) => Str::lower(Str::ascii($r['name'])),
            'progress' => fn ($r) => $r['progress'],
            'expiring' => fn ($r) => $r['counts']['expiring'],
            'expired'  => fn ($r) => $r['counts']['expired'],
            default    => fn ($r) => [$r['counts']['missing'], $r['counts']['expired'], $r['counts']['expiring']],
        };
    }

    private function scopeKey(): string
    {
        $user = auth()->user();

        return $user !== null && (ModuleAccess::onlyOwn($user, 'vendor') || ModuleAccess::onlyOwn($user, 'contract'))
            ? 'u' . $user->getKey()
            : 'all';
    }
}
