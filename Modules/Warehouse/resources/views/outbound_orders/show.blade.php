@extends('layouts.backend')
@section('title', $order->order_number)

@section('content')

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 text-sm">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
    <div class="card-body">
        <div class="flex flex-wrap items-start justify-between gap-4">

            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-2xl font-bold text-base-content font-mono">{{ $order->order_number }}</h1>
                    <span class="badge {{ $order->status->badgeClass() }} badge-sm">{{ $order->status->label() }}</span>
                    @if($order->status->value === 'completed')
                    @if($pendingActivationCount > 0)
                    <span class="badge badge-warning badge-sm">Chờ kích hoạt lưu hành ({{ $pendingActivationCount }})</span>
                    @elseif($tagsTotal > 0)
                    <span class="badge badge-success badge-sm">Đã kích hoạt lưu hành toàn bộ</span>
                    @endif
                    @endif
                </div>
                <p class="text-sm text-base-content/50">Lập ngày {{ $order->ordered_at->format('d/m/Y') }} · Tổng SL: {{ $order->totalQuantity() }} · Đã gán serial: {{ $tagsTotal }}</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('backend.outbound-orders.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
                <a href="{{ route('backend.outbound-orders.packing-slip', $order) }}" class="btn btn-outline btn-sm">In Phiếu xuất kho</a>
                @can('update', $order)
                <a href="{{ route('backend.outbound-orders.edit', $order) }}" class="btn btn-ghost btn-sm">Sửa</a>
                @if($order->status->value !== 'cancelled' && $pendingActivationCount > 0)
                <form method="POST" action="{{ route('backend.outbound-orders.activate-tags', $order) }}"
                      onsubmit="return confirm('Hành động này sẽ mở khóa hiển thị thông tin cho {{ $pendingActivationCount }} mã QR thuộc đơn hàng này. Tiếp tục?');">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">Kích hoạt lưu hành đơn hàng</button>
                </form>
                @endif
                @if($order->status->value === 'draft')
                <form method="POST" action="{{ route('backend.outbound-orders.complete', $order) }}" onsubmit="return confirm('Xuất kho đơn này? Tồn kho các lô và trạng thái tem QR sẽ được cập nhật, không thể hoàn tác.');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm" @disabled($order->pickedBatches->isEmpty())
                        @if($order->pickedBatches->isEmpty()) title="Chưa có lô nào được chọn — thêm ít nhất 1 lô ở khung gợi ý FEFO bên dưới trước." @endif
                    >Xuất kho</button>
                </form>
                <form method="POST" action="{{ route('backend.outbound-orders.cancel', $order) }}" onsubmit="return confirm('Hủy đơn xuất buôn này?');">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm text-error">Hủy đơn</button>
                </form>
                @endif
                @endcan
            </div>
        </div>

        <div class="divider my-4"></div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Đại lý</p>
                <p class="text-sm font-medium">{{ $order->dealer_name }}</p>
            </div>
            <div>
                <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Điện thoại</p>
                <p class="text-sm">{{ $order->dealer_phone ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Địa chỉ nhận hàng</p>
                <p class="text-sm">{{ $order->dealer_address ?: '—' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-6 items-start">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-4">Sản phẩm & Dải Serial đã xuất</h2>

            @if($order->pickedBatches->isEmpty())
            <p class="text-sm text-base-content/50">Chưa chọn lô hàng nào — dùng gợi ý FEFO bên phải để thêm.</p>
            @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Sản phẩm & Lô</th>
                            <th>Số lượng</th>
                            <th>Dải Tem GS1 (Serial)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->pickedBatches as $line)
                        @php($allocation = $allocations[$line->id] ?? ['segments' => [], 'assigned_count' => 0])
                        <tr>
                            <td class="align-top">
                                <div class="flex gap-3">
                                    <div class="avatar placeholder shrink-0">
                                        <div class="bg-neutral text-neutral-content rounded-lg w-10 h-10">
                                            <span class="text-sm">{{ mb_strtoupper(mb_substr($line->product->name, 0, 1)) }}</span>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium truncate">{{ $line->product->name }}</p>
                                        <a href="{{ route('backend.batches.show', $line->batch) }}" class="font-mono text-xs link link-hover">{{ $line->batch->internal_batch_code }}</a>
                                        <p class="text-xs text-base-content/50">HSD {{ $line->batch->exp_date->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="align-top">{{ $line->quantity }}</td>
                            <td class="align-top">
                                @if($allocation['assigned_count'] < $line->quantity)
                                <div class="alert alert-warning py-1.5 px-2.5 text-xs inline-flex">
                                    Chưa quét mã xuất kho — vui lòng dùng súng quét để gán serial cho đơn này trước khi giao.
                                </div>
                                @else
                                <div class="space-y-1">
                                    @foreach($allocation['segments'] as $segment)
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs">{{ $segment['label'] ?? 'Không seri hóa' }}</span>
                                        <span class="badge {{ $segment['status']->badgeClass() }} badge-xs">{{ $segment['status']->label() }}</span>
                                        <span class="text-xs text-base-content/40">({{ $segment['count'] }})</span>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </td>
                            <td class="align-top text-right">
                                @can('update', $order)
                                @if($order->status->value === 'draft')
                                <form method="POST" action="{{ route('backend.outbound-orders.batches.destroy', [$order, $line]) }}" onsubmit="return confirm('Bỏ lô này khỏi đơn?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs text-error">Bỏ</button>
                                </form>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">

        @if($order->notes)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-2">Ghi chú</h2>
                <p class="text-sm whitespace-pre-line">{{ $order->notes }}</p>
            </div>
        </div>
        @endif

        @can('update', $order)
        @if($order->status->value === 'draft')
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Gợi ý FEFO — chọn lô để xuất</h2>

                <form method="GET" action="{{ route('backend.outbound-orders.show', $order) }}" class="space-y-3 mb-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Sản phẩm</span></label>
                        <select name="product_id" class="select select-bordered select-sm w-full">
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected($selectedProductId === $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số lượng cần xuất</span></label>
                        <input type="number" name="requested_qty" value="{{ request('requested_qty', 0) }}" min="0" class="input input-bordered input-sm w-full">
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm w-full">Xem gợi ý lô (FEFO)</button>
                </form>

                @if($fefoSuggestions !== null)
                @if($fefoSuggestions->isEmpty())
                <p class="text-xs text-base-content/50">Sản phẩm này chưa có lô nào còn hàng trong kho — cần tạo <a href="{{ route('backend.inbound-receipts.create') }}" class="link">phiếu nhập kho</a> và hoàn tất sinh tem QR trước khi có thể xuất buôn.</p>
                @else
                <div class="space-y-2">
                    @foreach($fefoSuggestions as $row)
                    <form method="POST" action="{{ route('backend.outbound-orders.batches.store', $order) }}" class="flex items-center gap-2 border border-base-200 rounded-lg p-2">
                        @csrf
                        <input type="hidden" name="batch_id" value="{{ $row['batch']->id }}">
                        <div class="flex-1 text-xs">
                            <div class="font-mono font-medium">{{ $row['batch']->internal_batch_code }}</div>
                            <div class="text-base-content/50">HSD {{ $row['batch']->exp_date->format('d/m/Y') }} · Còn {{ $row['batch']->current_qty }}</div>
                        </div>
                        <input type="number" name="quantity" value="{{ $row['suggested'] }}" min="1" max="{{ $row['batch']->current_qty }}" class="input input-bordered input-xs w-20">
                        <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                    </form>
                    @endforeach
                </div>
                @endif
                @endif
            </div>
        </div>
        @endif
        @endcan

    </div>
</div>
@endsection
