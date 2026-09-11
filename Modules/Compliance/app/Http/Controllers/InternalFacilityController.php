<?php

namespace Modules\Compliance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Compliance\Actions\Backend\StoreInternalFacilityAction;
use Modules\Compliance\Actions\Backend\UpdateInternalFacilityAction;
use Modules\Compliance\Data\Requests\InternalFacilityData;
use Modules\Compliance\Models\InternalFacility;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Models\DocumentMasterType;

class InternalFacilityController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', InternalFacility::class);

        $facilities = InternalFacility::query()->orderBy('name')->get();

        $selectedFacility = $facilities->firstWhere('id', $request->query('facility'))
            ?? $facilities->first();

        $documentsByGroup = [
            DocumentGroupType::LegalFacility->value => collect(),
            DocumentGroupType::AttpQuality->value   => collect(),
            DocumentGroupType::Personnel->value     => collect(),
        ];

        if ($selectedFacility) {
            $selectedFacility->load(['documents' => fn ($q) => $q->with('documentType')->latest('issue_date')]);

            foreach ($selectedFacility->documents as $document) {
                $groupValue = $document->documentType->document_group->value;
                if (isset($documentsByGroup[$groupValue])) {
                    $documentsByGroup[$groupValue]->push($document);
                }
            }
        }

        $documentTypes = DocumentMasterType::query()
            ->applicableTo('internal')
            ->orderBy('document_group')
            ->orderBy('name')
            ->get();

        $documentTypesByGroup = [
            DocumentGroupType::LegalFacility->value => $documentTypes->where('document_group', DocumentGroupType::LegalFacility)->values(),
            DocumentGroupType::AttpQuality->value   => $documentTypes->where('document_group', DocumentGroupType::AttpQuality)->values(),
            DocumentGroupType::Personnel->value     => $documentTypes->where('document_group', DocumentGroupType::Personnel)->values(),
        ];

        return view('compliance::internal_compliance.index', [
            'facilities'           => $facilities,
            'selectedFacility'      => $selectedFacility,
            'documentTypes'         => $documentTypes,
            'documentTypesByGroup'  => $documentTypesByGroup,
            'documentsByGroup'      => $documentsByGroup,
        ]);
    }

    public function store(Request $request, StoreInternalFacilityAction $action): RedirectResponse
    {
        $this->authorize('create', InternalFacility::class);

        $data = InternalFacilityData::validateAndCreate($request->all());
        $facility = $action->handle($data);

        return redirect()->route('backend.internal-compliance.index', ['facility' => $facility->id])
            ->with('success', 'Đã thêm cơ sở "' . $facility->name . '".');
    }

    public function update(Request $request, InternalFacility $internalFacility, UpdateInternalFacilityAction $action): RedirectResponse
    {
        $this->authorize('update', $internalFacility);

        $data = InternalFacilityData::validateAndCreate($request->all());
        $action->handle($internalFacility, $data);

        return redirect()->route('backend.internal-compliance.index', ['facility' => $internalFacility->id])
            ->with('success', 'Đã cập nhật cơ sở "' . $internalFacility->name . '".');
    }
}
