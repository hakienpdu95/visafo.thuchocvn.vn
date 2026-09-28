<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\ChunkedUploadService;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Contract\Actions\Backend\DestroyContractAction;
use Modules\Contract\Actions\Backend\StoreContractAction;
use Modules\Contract\Actions\Backend\UpdateContractAction;
use Modules\Contract\Data\Requests\StoreContractData;
use Modules\Contract\Data\Requests\UpdateContractData;
use Modules\Contract\Enums\ContractPartyType;
use Modules\Contract\Enums\ContractStatus;
use Modules\Contract\Models\Contract;
use Modules\Contract\Models\ContractType;
use Modules\Contract\Queries\GetContractHandler;
use Modules\Contract\Queries\GetContractQuery;
use Modules\Customer\Models\Customer;
use Modules\Vendor\Enums\VendorSourceGroup;
use Modules\Vendor\Enums\VendorStatus;
use Modules\Vendor\Models\Vendor;

class ContractController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Contract::class, 'contract');
    }

    public function index()
    {
        $statuses = collect(ContractStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $contractTypes = ContractType::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($t) => ['value' => $t->id, 'text' => $t->name])
            ->all();

        $partyTypes = collect(ContractPartyType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $vendors   = Vendor::query()->orderBy('name')->get(['id', 'name']);
        $customers = Customer::query()->orderBy('name')->get(['id', 'name']);

        $sourceGroups = collect(VendorSourceGroup::cases())
            ->map(fn ($g) => ['value' => $g->value, 'text' => $g->label()])
            ->all();

        $vendorStatuses = collect(VendorStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('contract::index', compact('statuses', 'contractTypes', 'partyTypes', 'vendors', 'customers', 'sourceGroups', 'vendorStatuses'));
    }

    public function create(Request $request)
    {
        $lockedVendor = $request->filled('vendor_id')
            ? Vendor::query()->findOrFail($request->input('vendor_id'), ['id', 'name'])
            : null;

        $lockedCustomer = ! $lockedVendor && $request->filled('customer_id')
            ? Customer::query()->findOrFail($request->input('customer_id'), ['id', 'name'])
            : null;

        $isLocked      = $lockedVendor || $lockedCustomer;
        $vendors       = $isLocked ? collect() : Vendor::query()->orderBy('name')->get(['id', 'name']);
        $customers     = $isLocked ? collect() : Customer::query()->orderBy('name')->get(['id', 'name']);
        $contractTypes = ContractType::query()->orderBy('name')->get(['id', 'name']);

        return view('contract::create', compact('vendors', 'customers', 'contractTypes', 'lockedVendor', 'lockedCustomer'));
    }

    public function store(Request $request, StoreContractAction $action, ChunkedUploadService $chunkedUpload): RedirectResponse
    {
        if ($request->boolean('from_vendor')) {
            $request->merge(['type' => ContractPartyType::Input->value, 'customer_id' => null]);
        } elseif ($request->boolean('from_customer')) {
            $request->merge(['type' => ContractPartyType::Output->value, 'vendor_id' => null]);
        }

        $data     = StoreContractData::validateAndCreate($chunkedUpload->mergeIntoInput($request, $request->all()));
        $contract = $action->handle($data);

        if ($request->boolean('from_vendor') && $contract->vendor_id) {
            return redirect()->route('backend.vendors.show', ['vendor' => $contract->vendor_id, 'tab' => 'contracts'])
                ->with('success', 'Hợp đồng "' . $contract->name . '" đã được tạo thành công.');
        }

        if ($request->boolean('from_customer') && $contract->customer_id) {
            return redirect()->route('backend.customers.show', ['customer' => $contract->customer_id, 'tab' => 'contracts'])
                ->with('success', 'Hợp đồng "' . $contract->name . '" đã được tạo thành công.');
        }

        return redirect()->route('backend.contracts.show', $contract)
            ->with('success', 'Hợp đồng "' . $contract->name . '" đã được tạo thành công.');
    }

    public function show(Contract $contract, GetContractHandler $handler)
    {
        $contract = $handler->handle(new GetContractQuery($contract));

        return view('contract::show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $contract->loadMissing(['vendor', 'customer']);
        $contractTypes = ContractType::query()->orderBy('name')->get(['id', 'name']);

        return view('contract::edit', compact('contract', 'contractTypes'));
    }

    public function update(Request $request, Contract $contract, UpdateContractAction $action, ChunkedUploadService $chunkedUpload): RedirectResponse
    {
        $request->merge([
            'type'        => $contract->type->value,
            'vendor_id'   => $contract->vendor_id,
            'customer_id' => $contract->customer_id,
        ]);

        $data = UpdateContractData::validateAndCreate($chunkedUpload->mergeIntoInput($request, $request->all()));
        $action->handle($contract, $data);

        return redirect()->route('backend.contracts.show', $contract)
            ->with('success', 'Cập nhật hợp đồng thành công.');
    }

    public function destroyMedia(Contract $contract, Media $media, MediaUploadService $uploadService): JsonResponse
    {
        $this->authorize('update', $contract);
        abort_unless($media->model_type === $contract->getMorphClass() && $media->model_id === $contract->id, 404);

        $uploadService->delete($media);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, Contract $contract, DestroyContractAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($contract);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa hợp đồng "' . $name . '".']);
        }

        return redirect()->route('backend.contracts.index')
            ->with('success', 'Đã xóa hợp đồng "' . $name . '".');
    }
}
