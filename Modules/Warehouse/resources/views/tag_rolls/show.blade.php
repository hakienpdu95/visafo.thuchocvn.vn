@extends('layouts.backend')
@section('title', 'Cuộn tem ' . ($roll->prefix ?: $roll->id))

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content font-mono">{{ $roll->prefix ?: '(không prefix)' }} · {{ $roll->from_sequence }}–{{ $roll->to_sequence }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">In lúc {{ $roll->created_at?->format('d/m/Y H:i') }} bởi {{ $roll->creator?->name ?? 'Hệ thống (CLI)' }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.tag-rolls.index') }}" class="btn btn-ghost btn-sm">Quay lại danh sách</a>
        <a href="{{ route('backend.tag-rolls.download-pdf', ['prefix' => $roll->prefix, 'from' => $roll->from_sequence, 'to' => $roll->to_sequence]) }}" class="btn btn-primary btn-sm">Tải PDF để in</a>
        <a href="{{ route('backend.tag-rolls.download-csv', ['prefix' => $roll->prefix, 'from' => $roll->from_sequence, 'to' => $roll->to_sequence]) }}" class="btn btn-ghost btn-sm">Tải CSV</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-4">
            <p class="text-xs text-base-content/50">Tổng số tem trong cuộn</p>
            <p class="text-2xl font-bold">{{ number_format($roll->count) }}</p>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-4">
            <p class="text-xs text-base-content/50">Chưa gắn kết (còn dùng được)</p>
            <p class="text-2xl font-bold">{{ number_format($counts['provisioned']) }}</p>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-4">
            <p class="text-xs text-base-content/50">Đã gắn kết / kích hoạt</p>
            <p class="text-2xl font-bold">{{ number_format($counts['bound']) }}</p>
        </div>
    </div>
</div>

<p class="text-xs text-base-content/50 mb-4">
    Để gán tem, vào trang chi tiết Lô hàng cần dán tem và dùng card "Kích hoạt Tem Truy vết cho Lô hàng".
</p>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body">
        <h2 class="text-base font-semibold mb-1">Bản đồ phân bổ dải số</h2>
        <p class="text-xs text-base-content/50 mb-3">Từng phân khúc liên tục theo lô hàng/trạng thái — biết chính xác dải nào đã dùng, dải nào còn trống.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Dải số</th>
                        <th>Số lượng</th>
                        <th>Trạng thái</th>
                        <th>Lô hàng đã gán</th>
                        <th>Ngày thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($segments as $segment)
                    <tr>
                        <td class="font-mono">{{ $segment['from'] }}{{ $segment['to'] > $segment['from'] ? '–' . $segment['to'] : '' }}</td>
                        <td>{{ number_format($segment['count']) }}</td>
                        <td><span class="badge {{ $segment['status']->badgeClass() }} badge-sm">{{ $segment['status']->label() }}</span></td>
                        <td>
                            @if($segment['batch'])
                            <a href="{{ route('backend.batches.show', $segment['batch']) }}" class="link link-primary font-mono">{{ $segment['batch']->internal_batch_code }}</a>
                            <span class="text-base-content/50">— {{ $segment['batch']->product?->name }}</span>
                            @else
                            <span class="text-base-content/40">Chưa sử dụng</span>
                            @endif
                        </td>
                        <td class="text-xs text-base-content/50">{{ $segment['updated_at']?->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
