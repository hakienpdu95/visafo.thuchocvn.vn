<?php

namespace Modules\HanoiCheckOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Models\HanoiCheckSyncLog;
use Modules\HanoiCheckOrder\Actions\Backend\SaveHanoiCheckTransporterAction;
use Modules\HanoiCheckOrder\Data\Requests\SaveHanoiCheckTransporterData;
use Modules\HanoiCheckOrder\Models\HanoiCheckTransporter;

class HanoiCheckTransporterController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageTransporters', HanoiCheckTransporter::class);

        return view('hanoicheckorder::transporters', [
            'listUrl'  => route('backend.hanoicheck-transporters.list'),
            'storeUrl' => route('backend.hanoicheck-transporters.store'),
        ]);
    }

    public function list(): JsonResponse
    {
        $this->authorize('manageTransporters', HanoiCheckTransporter::class);

        $last = HanoiCheckSyncLog::query()
            ->where('entity_type', SyncEntity::Transporter->value)
            ->orderByDesc('created_at')
            ->get(['local_id', 'status', 'error_message', 'created_at'])
            ->unique('local_id')->keyBy('local_id');

        return response()->json(['data' => HanoiCheckTransporter::query()->orderByDesc('is_active')->orderBy('name')->get()
            ->map(fn (HanoiCheckTransporter $t) => [
                'id'             => $t->id,
                'code'           => $t->code,
                'name'           => $t->name,
                'phone'          => $t->phone,
                'cccd'           => $t->cccd,
                'date_of_birth'  => $t->date_of_birth?->toDateString(),
                'address'        => $t->address,
                'transport_mean' => $t->transport_mean,
                'license_plate'  => $t->license_plate,
                'is_active'      => $t->is_active,
                'sync_status'    => $last->get($t->id)?->status->value,
                'sync_error'     => $last->get($t->id)?->error_message,
                'synced_at'      => $last->get($t->id)?->created_at->format('H:i d/m/Y'),
                'update_url'     => route('backend.hanoicheck-transporters.update', $t),
            ])->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manageTransporters', HanoiCheckTransporter::class);

        $t = SaveHanoiCheckTransporterAction::run(null, SaveHanoiCheckTransporterData::validateAndCreate($request->all()));

        return response()->json(['message' => "Đã thêm người giao hàng {$t->name} ({$t->code})."]);
    }

    public function update(Request $request, HanoiCheckTransporter $transporter): JsonResponse
    {
        $this->authorize('manageTransporters', HanoiCheckTransporter::class);

        SaveHanoiCheckTransporterAction::run($transporter, SaveHanoiCheckTransporterData::validateAndCreate($request->all()));

        return response()->json(['message' => "Đã cập nhật người giao hàng {$transporter->name}."]);
    }
}
