<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Contract\Actions\Backend\DestroyVendorComplianceRequirementAction;
use Modules\Contract\Actions\Backend\SaveVendorComplianceRequirementAction;
use Modules\Contract\Data\Requests\SaveVendorComplianceRequirementData;
use Modules\Contract\Enums\ComplianceRequirementKind;
use Modules\Contract\Models\ContractType;
use Modules\Contract\Models\VendorComplianceRequirement;
use Modules\Product\Models\DocumentMasterType;
use Modules\Vendor\Enums\VendorSourceGroup;
use Modules\Vendor\Enums\VendorStatus;
use Modules\Vendor\Models\Vendor;

class VendorComplianceRequirementController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', VendorComplianceRequirement::class);

        $groups = VendorComplianceRequirement::query()
            ->with(['documentType:id,name', 'contractType:id,name'])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (VendorComplianceRequirement $r) => $r->groupIdentifier())
            ->map(function ($rows, $key) {
                $head = $rows->first();

                return [
                    'key'          => $key,
                    'kind'         => $head->kind->value,
                    'kind_label'   => $head->kind->label(),
                    'label'        => $head->label,
                    'source_group' => $head->source_group?->value,
                    'is_mandatory' => $head->is_mandatory,
                    'warning_days' => $head->warning_days,
                    'legal_basis'  => $head->legal_basis,
                    'target_ids'   => $rows->map(fn ($r) => $r->document_master_type_id ?? $r->contract_type_id)->values()->all(),
                    'targets'      => $rows->map(fn ($r) => $r->documentType?->name ?? $r->contractType?->name ?? '—')->values()->all(),
                ];
            })
            ->values();

        $vendorCounts = Vendor::query()
            ->where('status', VendorStatus::Active->value)
            ->selectRaw('source_group, count(*) as total')
            ->groupBy('source_group')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->source_group?->value ?? '' => (int) $row->total]);

        $scopes = collect([['value' => '', 'text' => 'Áp dụng cho mọi NCC', 'vendors' => (int) $vendorCounts->sum()]])
            ->concat(collect(VendorSourceGroup::cases())->map(fn ($g) => [
                'value'   => $g->value,
                'text'    => $g->label(),
                'vendors' => (int) $vendorCounts->get($g->value, 0),
            ]))
            ->map(fn ($scope) => $scope + ['groups' => $groups->where('source_group', $scope['value'] ?: null)->values()->all()])
            ->all();

        $options = [
            ComplianceRequirementKind::Document->value => DocumentMasterType::query()
                ->applicableTo('vendor')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($t) => ['value' => $t->id, 'text' => $t->name])
                ->all(),
            ComplianceRequirementKind::Contract->value => ContractType::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($t) => ['value' => $t->id, 'text' => $t->name])
                ->all(),
        ];

        $kinds = collect(ComplianceRequirementKind::cases())
            ->map(fn ($k) => ['value' => $k->value, 'text' => $k->label()])
            ->all();

        return view('contract::compliance_requirements.index', compact('scopes', 'options', 'kinds'));
    }

    public function store(Request $request, SaveVendorComplianceRequirementAction $action): RedirectResponse
    {
        $this->authorize('create', VendorComplianceRequirement::class);

        $data = SaveVendorComplianceRequirementData::validateAndCreate($this->input($request));
        $action->handle($data);

        return redirect()->route('backend.vendor-compliance-requirements.index')
            ->with('success', 'Đã thêm yêu cầu "' . $data->label . '".');
    }

    public function update(Request $request, string $group, SaveVendorComplianceRequirementAction $action): RedirectResponse
    {
        $this->authorize('update', VendorComplianceRequirement::class);
        abort_unless(VendorComplianceRequirement::query()->inGroup($group)->exists(), 404);

        $data = SaveVendorComplianceRequirementData::validateAndCreate($this->input($request));
        $action->handle($data, $group);

        return redirect()->route('backend.vendor-compliance-requirements.index')
            ->with('success', 'Đã cập nhật yêu cầu "' . $data->label . '".');
    }

    public function destroy(string $group, DestroyVendorComplianceRequirementAction $action): RedirectResponse
    {
        $this->authorize('delete', VendorComplianceRequirement::class);

        $label = $action->handle($group);

        return redirect()->route('backend.vendor-compliance-requirements.index')
            ->with('success', 'Đã xóa yêu cầu "' . $label . '".');
    }

    private function input(Request $request): array
    {
        return array_merge($request->all(), [
            'is_mandatory' => $request->boolean('is_mandatory'),
            'source_group' => $request->input('source_group') ?: null,
            'target_ids'   => array_values(array_filter((array) $request->input('target_ids', []))),
        ]);
    }
}
