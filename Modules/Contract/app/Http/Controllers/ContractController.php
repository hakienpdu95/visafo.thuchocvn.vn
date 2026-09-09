<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Contract\Actions\Backend\DestroyContractAction;
use Modules\Contract\Actions\Backend\StoreContractAction;
use Modules\Contract\Actions\Backend\UpdateContractAction;
use Modules\Contract\Data\Requests\StoreContractData;
use Modules\Contract\Data\Requests\UpdateContractData;
use Modules\Contract\Enums\ContractStatus;
use Modules\Contract\Models\Contract;
use Modules\Contract\Models\ContractType;
use Modules\Contract\Queries\GetContractHandler;
use Modules\Contract\Queries\GetContractQuery;
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

        return view('contract::index', compact('statuses'));
    }

    public function create()
    {
        $vendors       = Vendor::query()->orderBy('name')->get(['id', 'name']);
        $contractTypes = ContractType::query()->orderBy('name')->get(['id', 'name']);

        return view('contract::create', compact('vendors', 'contractTypes'));
    }

    public function store(Request $request, StoreContractAction $action): RedirectResponse
    {
        $data     = StoreContractData::validateAndCreate($request->all());
        $contract = $action->handle($data);

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
        $vendors       = Vendor::query()->orderBy('name')->get(['id', 'name']);
        $contractTypes = ContractType::query()->orderBy('name')->get(['id', 'name']);

        return view('contract::edit', compact('contract', 'vendors', 'contractTypes'));
    }

    public function update(Request $request, Contract $contract, UpdateContractAction $action): RedirectResponse
    {
        $data = UpdateContractData::validateAndCreate($request->all());
        $action->handle($contract, $data);

        return redirect()->route('backend.contracts.show', $contract)
            ->with('success', 'Cập nhật hợp đồng thành công.');
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
