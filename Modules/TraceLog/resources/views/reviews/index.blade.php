@extends('layouts.backend')
@section('title', 'Quản lý Đánh giá & Phản hồi')

@section('content')
@php
    use Modules\SalesOrder\Enums\TraceReviewStatus;
    use Modules\SalesOrder\Enums\TraceReviewType;
    use Modules\SalesOrder\Models\TraceReview;
@endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Quản lý Đánh giá &amp; Phản hồi</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Đánh giá và báo sự cố khách gửi từ trang truy xuất — mỗi bản ghi gắn với đúng mã tem đã quét</p>
    </div>
    <div class="flex gap-2 text-sm">
        <span class="badge badge-warning gap-1">{{ $pendingCounts[TraceReviewType::Rating->value] ?? 0 }} đánh giá chờ duyệt</span>
        <span class="badge badge-error gap-1">{{ $pendingCounts[TraceReviewType::Issue->value] ?? 0 }} sự cố mới</span>
    </div>
</div>

<form method="GET" class="card bg-base-100 mb-4">
    <div class="card-body py-3 px-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
        <label class="form-control sm:col-span-2">
            <span class="label-text text-xs font-medium mb-1">Tìm kiếm</span>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã TXNG, số đơn, khách hàng, sản phẩm, nội dung…" class="input input-sm input-bordered w-full">
        </label>
        <label class="form-control">
            <span class="label-text text-xs font-medium mb-1">Loại</span>
            <select name="type" class="select select-sm select-bordered w-full" onchange="this.form.submit()">
                <option value="">Tất cả</option>
                @foreach(TraceReviewType::cases() as $type)
                <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="form-control">
            <span class="label-text text-xs font-medium mb-1">Trạng thái</span>
            <select name="status" class="select select-sm select-bordered w-full" onchange="this.form.submit()">
                <option value="">Tất cả</option>
                @foreach(TraceReviewStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
    </div>
</form>

<div class="card bg-base-100 border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr class="text-xs">
                    <th class="w-32">Thời gian</th>
                    <th>Nội dung</th>
                    <th class="w-64">Truy vết (tem / đơn / lô)</th>
                    <th class="w-28">Trạng thái</th>
                    <th class="w-40 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                @php $canModerate = auth()->user()->can('moderate', $review); @endphp
                <tr class="align-top {{ $review->status === TraceReviewStatus::Pending ? 'bg-warning/5' : '' }}">
                    <td class="text-xs text-base-content/60 whitespace-nowrap">{{ $review->created_at->format('d/m/Y H:i') }}</td>
                    <td class="min-w-[18rem]">
                        <div class="flex flex-wrap items-center gap-1.5 mb-1">
                            <span class="badge badge-sm {{ $review->type === TraceReviewType::Issue ? 'badge-error' : 'badge-info' }}">{{ $review->type->label() }}</span>
                            @if($review->type === TraceReviewType::Issue)
                            <span class="text-xs font-semibold">{{ $review->issueCategoryLabel() }}</span>
                            @else
                            <span class="text-xs font-semibold text-amber-600">★ {{ number_format($review->averageScore(), 1, ',', '') }}</span>
                            <span class="text-[11px] text-base-content/50">
                                @foreach(TraceReview::SCORES as $col => $label){{ $label }}: <b>{{ $review->{$col} }}</b>{{ $loop->last ? '' : ' · ' }}@endforeach
                            </span>
                            @endif
                        </div>
                        @if($review->comment)
                        <p class="text-sm whitespace-pre-line">{{ $review->comment }}</p>
                        @else
                        <p class="text-xs italic text-base-content/40">Không có nhận xét</p>
                        @endif
                        @if($review->type === TraceReviewType::Rating)
                        <p class="mt-1 text-[11px] {{ $review->is_public_requested ? 'text-success' : 'text-base-content/40' }}">
                            {{ $review->is_public_requested ? '✓ Khách đồng ý công khai nhận xét' : 'Khách không đồng ý công khai — duyệt chỉ để tính điểm' }}
                        </p>
                        @endif
                    </td>
                    <td class="text-xs space-y-0.5">
                        <p>Tem: <a href="{{ route('trace.show', $review->trace_code) }}" target="_blank" rel="noopener" class="link link-primary font-mono">{{ strtoupper($review->trace_code) }}</a></p>
                        @if($review->salesOrder)
                        <p>Đơn: <a href="{{ route('backend.sales-orders.show', $review->sales_order_id) }}" class="link font-mono">{{ $review->salesOrder->misa_ref_id }}</a></p>
                        <p class="text-base-content/60 truncate max-w-[15rem]" title="{{ $review->salesOrder->customer_name }}">{{ $review->salesOrder->customer_name }}</p>
                        @endif
                        <p>SP: {{ $review->product?->name ?? '—' }}</p>
                        <p class="text-base-content/60 font-mono">{{ $review->productBatch?->batch_code ?? $review->printLog?->batch_code ?? '—' }}</p>
                    </td>
                    <td>
                        <span class="badge badge-sm {{ $review->status->badgeClass() }}">{{ $review->status->label($review->type) }}</span>
                        @if($review->reviewed_at)
                        <p class="mt-1 text-[11px] text-base-content/50">{{ $review->reviewer?->name }}<br>{{ $review->reviewed_at->format('d/m H:i') }}</p>
                        @endif
                    </td>
                    <td class="text-right whitespace-nowrap">
                        @if($canModerate)
                        @if($review->status !== TraceReviewStatus::Approved)
                        <form method="POST" action="{{ route('backend.trace-reviews.approve', $review) }}" class="inline">@csrf @method('PUT')
                            <button class="btn btn-xs btn-success text-white">{{ $review->type === TraceReviewType::Issue ? 'Đã xử lý' : 'Duyệt' }}</button>
                        </form>
                        @endif
                        @if($review->status !== TraceReviewStatus::Rejected)
                        <form method="POST" action="{{ route('backend.trace-reviews.reject', $review) }}" class="inline">@csrf @method('PUT')
                            <button class="btn btn-xs btn-ghost">{{ $review->type === TraceReviewType::Issue ? 'Bỏ qua' : 'Ẩn' }}</button>
                        </form>
                        @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-10 text-center text-sm text-base-content/40">Chưa có đánh giá / phản hồi nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reviews->hasPages())
    <div class="p-3 border-t border-base-200">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection
