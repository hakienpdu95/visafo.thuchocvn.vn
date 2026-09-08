@extends('layouts.backend')
@section('title', 'Báo cáo sự cố — ' . $batch->internal_batch_code)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content font-mono">{{ $batch->internal_batch_code }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Báo cáo sự cố / tác dụng bất lợi (Phụ lục 18-MP) · {{ $reports->total() }} báo cáo</p>
    </div>
    <a href="{{ route('backend.batches.show', $batch) }}" class="btn btn-ghost btn-sm">Quay lại lô hàng</a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Ngày nhận báo cáo</th>
                    <th>Người tiêu dùng</th>
                    <th>Mô tả phản ứng</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td class="whitespace-nowrap">{{ $report->received_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $report->consumer_name ?? '—' }}</td>
                    <td class="max-w-xs truncate">{{ $report->reaction_description }}</td>
                    <td><span class="badge {{ $report->status->badgeClass() }} badge-xs">{{ $report->status->label() }}</span></td>
                    <td class="text-right">
                        <a href="{{ route('backend.adverse-event-reports.show', $report) }}" class="btn btn-ghost btn-xs">Xem</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-sm text-base-content/50 py-6">Lô này chưa có báo cáo sự cố nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->hasPages())
    <div class="card-body py-3 px-4 border-t border-base-200">
        {{ $reports->links() }}
    </div>
    @endif
</div>
@endsection
