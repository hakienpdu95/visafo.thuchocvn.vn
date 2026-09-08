@extends('layouts.backend')
@section('title', 'Lịch sử thao tác — ' . $batch->internal_batch_code)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content font-mono">{{ $batch->internal_batch_code }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Lịch sử thao tác (Audit Trail) · {{ $logs->total() }} bản ghi</p>
    </div>
    <a href="{{ route('backend.batches.show', $batch) }}" class="btn btn-ghost btn-sm">Quay lại lô hàng</a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người thao tác</th>
                    <th>Hành động</th>
                    <th>Thay đổi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'Hệ thống' }}</td>
                    <td><span class="badge badge-ghost badge-sm">{{ $log->action }}</span></td>
                    <td class="text-xs">
                        @php($old = $log->oldValues())
                        @php($new = $log->newValues())
                        @if(empty($old) && empty($new))
                            <span class="text-base-content/40">—</span>
                        @else
                            @foreach(array_keys($new + $old) as $field)
                                <div><span class="font-medium">{{ $field }}</span>: {{ $old[$field] ?? '—' }} → {{ $new[$field] ?? '—' }}</div>
                            @endforeach
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-sm text-base-content/50 py-6">Chưa có thao tác nào được ghi nhận trên lô này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-body py-3 px-4 border-t border-base-200">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
