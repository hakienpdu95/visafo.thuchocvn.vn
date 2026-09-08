@extends('layouts.backend')
@section('title', 'Thu hồi — ' . $recall->product->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $recall->product->name }}
            <span class="badge {{ $recall->status->badgeClass() }} badge-sm">{{ $recall->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $recall->batch ? 'Lô ' . $recall->batch->internal_batch_code : 'Toàn bộ SKU' }} · Khởi tạo {{ $recall->initiated_at->format('d/m/Y H:i') }}
            @if($recall->initiator) bởi {{ $recall->initiator->name }} @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.product-recalls.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $recall)
        @if($recall->status->value === 'active')
        <form method="POST" action="{{ route('backend.product-recalls.complete', $recall) }}">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">Đánh dấu hoàn tất</button>
        </form>
        <form method="POST" action="{{ route('backend.product-recalls.cancel', $recall) }}" onsubmit="return confirm('Hủy chiến dịch thu hồi này?');">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm text-error">Hủy</button>
        </form>
        @endif
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6 items-start">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-4">Khách hàng cần liên hệ thu hồi</h2>
            <p class="text-xs text-base-content/50 mb-3">Truy vết ngược từ tem QR đã bán qua Sapo — chỉ hiển thị các đơn xác định được số điện thoại.</p>

            @if($affectedCustomers->isEmpty())
            <p class="text-sm text-base-content/50">Chưa có khách hàng nào cần liên hệ (chưa bán, hoặc chưa xác định được người mua).</p>
            @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Mã đơn hàng</th>
                            <th>Mã tem QR</th>
                            <th>Ngày bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($affectedCustomers as $row)
                        <tr>
                            <td>{{ $row['customer_name'] ?? '—' }}</td>
                            <td class="font-mono">{{ $row['customer_phone'] }}</td>
                            <td class="font-mono text-xs">{{ $row['order_code'] ?? '—' }}</td>
                            <td class="font-mono text-xs">{{ $row['qr_code'] }}</td>
                            <td>{{ $row['sold_at']?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Chi tiết</h2>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-base-content/50 text-xs">Mức độ</dt><dd>{{ $recall->severity?->label() ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Lý do</dt><dd class="whitespace-pre-line">{{ $recall->reason }}</dd></div>
                    @if($recall->notes)
                    <div><dt class="text-base-content/50 text-xs">Ghi chú</dt><dd class="whitespace-pre-line">{{ $recall->notes }}</dd></div>
                    @endif
                    @if($recall->completed_at)
                    <div><dt class="text-base-content/50 text-xs">Hoàn tất lúc</dt><dd>{{ $recall->completed_at->format('d/m/Y H:i') }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
