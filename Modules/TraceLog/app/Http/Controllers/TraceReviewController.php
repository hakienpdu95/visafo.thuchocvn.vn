<?php

namespace Modules\TraceLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\SalesOrder\Enums\TraceReviewStatus;
use Modules\SalesOrder\Enums\TraceReviewType;
use Modules\SalesOrder\Models\TraceReview;

/** Quản lý Đánh giá & Phản hồi gửi từ trang truy xuất: xem mã tem / đơn hàng / lô, duyệt công khai hoặc ẩn. */
class TraceReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TraceReview::class);

        $filters = $request->validate([
            'type'   => ['nullable', Rule::enum(TraceReviewType::class)],
            'status' => ['nullable', Rule::enum(TraceReviewStatus::class)],
            'q'      => ['nullable', 'string', 'max:100'],
        ]);

        $reviews = TraceReview::query()
            ->with(['salesOrder:id,misa_ref_id,customer_name', 'product:id,name,sku', 'printLog:id,batch_code,created_at', 'productBatch:id,batch_code', 'reviewer:id,name'])
            ->when($filters['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['q'] ?? null, fn (Builder $q, $v) => $q->where(fn (Builder $w) => $w
                ->where('trace_code', 'like', '%' . strtolower($v) . '%')
                ->orWhere('comment', 'like', "%{$v}%")
                ->orWhereHas('salesOrder', fn (Builder $o) => $o->where('misa_ref_id', 'like', "%{$v}%")->orWhere('customer_name', 'like', "%{$v}%"))
                ->orWhereHas('product', fn (Builder $p) => $p->where('name', 'like', "%{$v}%"))))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCounts = TraceReview::query()->where('status', TraceReviewStatus::Pending->value)
            ->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type');

        return view('tracelog::reviews.index', compact('reviews', 'filters', 'pendingCounts'));
    }

    public function approve(TraceReview $traceReview): RedirectResponse
    {
        return $this->moderate($traceReview, TraceReviewStatus::Approved);
    }

    public function reject(TraceReview $traceReview): RedirectResponse
    {
        return $this->moderate($traceReview, TraceReviewStatus::Rejected);
    }

    private function moderate(TraceReview $review, TraceReviewStatus $status): RedirectResponse
    {
        $this->authorize('moderate', $review);

        $review->update(['status' => $status, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        $message = match (true) {
            $review->type === TraceReviewType::Issue  => 'Đã cập nhật phản hồi: ' . mb_strtolower($status->label($review->type)) . '.',
            $status === TraceReviewStatus::Approved   => $review->is_public_requested
                ? 'Đã duyệt — đánh giá được tính điểm và hiện công khai.'
                : 'Đã duyệt — đánh giá được tính điểm (khách không đồng ý công khai nên nhận xét không hiện).',
            default                                   => 'Đã ẩn đánh giá.',
        };

        return back()->with('success', $message);
    }
}
