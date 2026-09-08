@extends('layouts.backend')
@section('title', 'Báo cáo tác dụng bất lợi')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Báo cáo tác dụng bất lợi (Phụ lục 18-MP)</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Khiếu nại dị ứng/phản ứng bất lợi với mỹ phẩm — báo cáo sơ bộ trong 7 ngày, chi tiết trong 8 ngày tiếp theo</p>
    </div>
    @can('create', \Modules\Recall\Models\AdverseEventReport::class)
    <a href="{{ route('backend.adverse-event-reports.create') }}" class="btn btn-primary btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Ghi nhận khiếu nại mới
    </a>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body py-3 px-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs">Trạng thái</span></label>
                <select name="status" class="select select-bordered select-sm">
                    <option value="">Tất cả</option>
                    @foreach($statuses as $status)
                    <option value="{{ $status['value'] }}" @selected(request('status') === $status['value'])>{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline">Lọc</button>
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Người sử dụng</th>
                    <th>Ngày nhận khiếu nại</th>
                    <th>Hạn báo cáo sơ bộ</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td>
                        <a href="{{ route('backend.adverse-event-reports.show', $report) }}" class="link link-hover font-medium">{{ $report->product->name }}</a>
                    </td>
                    <td>{{ $report->consumer_name }}</td>
                    <td>{{ $report->received_at->format('d/m/Y') }}</td>
                    <td>
                        {{ $report->preliminaryDeadline()->format('d/m/Y') }}
                        @if($report->status->value === 'draft' && $report->preliminaryDeadline()->isPast())
                        <span class="badge badge-error badge-xs ml-1">Quá hạn</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $report->status->badgeClass() }} badge-sm">{{ $report->status->label() }}</span></td>
                    <td class="text-right">
                        <a href="{{ route('backend.adverse-event-reports.show', $report) }}" class="btn btn-ghost btn-xs">Xem</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-sm text-base-content/50 py-6">Chưa có báo cáo nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->hasPages())
    <div class="card-body py-3 px-4 border-t border-base-200">
        {{ $reports->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
