<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\ActivityLog\Models\AuditLog;
use Modules\Warehouse\Actions\Backend\ActivateBatchTagsAction;
use Modules\Warehouse\Actions\Backend\BindRetailItemTagRangeAction;
use Modules\Warehouse\Actions\Backend\RecallBatchAction;
use Modules\Warehouse\Actions\Backend\UnbindRetailItemTagRangeAction;
use Modules\Warehouse\Enums\BatchStatus;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\TagRoll;
use Modules\Warehouse\Queries\GetBatchHandler;
use Modules\Warehouse\Queries\GetBatchQuery;
use Modules\Warehouse\Queries\GetBatchTagAllocationsHandler;
use Modules\Warehouse\Queries\GetBatchTagAllocationsQuery;

class BatchController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Batch::class, 'batch');
    }

    public function index()
    {
        $statuses = collect(BatchStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('warehouse::batches.index', compact('statuses'));
    }

    public function show(Batch $batch, GetBatchHandler $handler, GetBatchTagAllocationsHandler $allocationsHandler)
    {
        $batch = $handler->handle(new GetBatchQuery($batch));

        $tagCounts = $batch->tags()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $tagsTotal = $tagCounts->sum();

        $remainingToTag = max(0, $batch->initial_qty - $tagsTotal);

        $availableRolls = TagRoll::latest('created_at')->limit(100)->get()
            ->map(function (TagRoll $roll) {
                $roll->setAttribute('live_counts', $roll->liveCounts());

                return $roll;
            })
            ->filter(fn (TagRoll $roll) => $roll->live_counts['provisioned'] > 0)
            ->values();

        $allocations = $allocationsHandler->handle(new GetBatchTagAllocationsQuery($batch));

        return view('warehouse::batches.show', compact('batch', 'tagCounts', 'tagsTotal', 'remainingToTag', 'availableRolls', 'allocations'));
    }

    public function bindTagsRange(Request $request, Batch $batch, BindRetailItemTagRangeAction $action): RedirectResponse
    {
        $this->authorize('update', $batch);

        $validated = $request->validate([
            'roll_id'        => ['required', 'string', 'exists:tag_rolls,id'],
            'start_sequence' => ['required', 'integer', 'min:1'],
            'end_sequence'   => ['required', 'integer', 'min:1', 'gte:start_sequence'],
        ]);

        $roll = TagRoll::findOrFail($validated['roll_id']);

        $requestedCount   = (int) $validated['end_sequence'] - (int) $validated['start_sequence'] + 1;
        $alreadyTagged    = $batch->tags()->count();
        $remainingToTag   = max(0, $batch->initial_qty - $alreadyTagged);

        if ($requestedCount > $remainingToTag) {
            return redirect()->route('backend.batches.show', $batch)
                ->with('error', "Lô này chỉ còn cần {$remainingToTag} tem, nhưng dải đã chọn có {$requestedCount} tem. Vui lòng thu hẹp dải số.")
                ->withInput();
        }

        if ((int) $validated['start_sequence'] < $roll->from_sequence || (int) $validated['end_sequence'] > $roll->to_sequence) {
            return redirect()->route('backend.batches.show', $batch)
                ->with('error', "Dải số đã chọn nằm ngoài phạm vi cuộn tem \"{$roll->prefix}\" ({$roll->from_sequence}–{$roll->to_sequence}).")
                ->withInput();
        }

        try {
            $bound = $action->handle(
                $batch,
                (int) $validated['start_sequence'],
                (int) $validated['end_sequence'],
                $roll->prefix,
            );
        } catch (ValidationException $e) {
            return redirect()->route('backend.batches.show', $batch)
                ->with('error', collect($e->errors())->flatten()->first())
                ->withInput();
        }

        return redirect()->route('backend.batches.show', $batch)
            ->with('success', "Đã gắn kết {$bound} tem (cuộn \"{$roll->prefix}\", dải {$validated['start_sequence']}–{$validated['end_sequence']}) cho lô \"{$batch->internal_batch_code}\". Tem đang ở trạng thái \"Chờ lưu hành\" — bấm \"Kích hoạt lưu hành\" khi sẵn sàng cho phép quét công khai.");
    }

    public function activateTags(Batch $batch, ActivateBatchTagsAction $action): RedirectResponse
    {
        $this->authorize('update', $batch);

        $activated = $action->handle($batch);

        return redirect()->route('backend.batches.show', $batch)
            ->with('success', $activated > 0
                ? "Đã kích hoạt lưu hành {$activated} tem cho lô \"{$batch->internal_batch_code}\". Quét mã sẽ hiển thị thông tin sản phẩm ngay."
                : 'Lô này không có tem nào đang chờ lưu hành.');
    }

    public function unbindTagRange(Request $request, Batch $batch, UnbindRetailItemTagRangeAction $action): RedirectResponse
    {
        $this->authorize('update', $batch);

        $validated = $request->validate([
            'from_sequence' => ['required', 'integer', 'min:1'],
            'to_sequence'   => ['required', 'integer', 'min:1', 'gte:from_sequence'],
        ]);

        try {
            $unbound = $action->handle($batch, (int) $validated['from_sequence'], (int) $validated['to_sequence']);
        } catch (ValidationException $e) {
            return redirect()->route('backend.batches.show', $batch)
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('backend.batches.show', $batch)
            ->with('success', "Đã gỡ {$unbound} tem (dải {$validated['from_sequence']}–{$validated['to_sequence']}) khỏi lô \"{$batch->internal_batch_code}\". Tem đã quay về kho tiền định danh, có thể gán lại cho lô khác.");
    }

    public function recall(Batch $batch, RecallBatchAction $action): RedirectResponse
    {
        $this->authorize('recall', $batch);

        $action->handle($batch);

        return redirect()->route('backend.batches.show', $batch)
            ->with('success', 'Đã đánh dấu lô "' . $batch->internal_batch_code . '" là thu hồi.');
    }

    public function auditTrail(Batch $batch): View
    {
        $this->authorize('view', $batch);

        $logs = AuditLog::where('model_type', Batch::class)
            ->where('model_id', $batch->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('warehouse::batches.audit_trail', compact('batch', 'logs'));
    }

    public function sapoSyncLog(Batch $batch): View
    {
        $this->authorize('view', $batch);

        $entries = $batch->tags()
            ->whereNotNull('external_order_id')
            ->with('externalOrder')
            ->orderByDesc('sold_at')
            ->paginate(25);

        return view('warehouse::batches.sapo_sync_log', compact('batch', 'entries'));
    }

    public function incidents(Batch $batch): View
    {
        $this->authorize('view', $batch);

        $reports = $batch->adverseEventReports()
            ->orderByDesc('received_at')
            ->paginate(25);

        return view('warehouse::batches.incidents', compact('batch', 'reports'));
    }
}
