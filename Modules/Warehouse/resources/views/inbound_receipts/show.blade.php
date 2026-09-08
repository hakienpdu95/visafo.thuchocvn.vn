@extends('layouts.backend')
@section('title', $inboundReceipt->receipt_number)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $inboundReceipt->receipt_number }}
            <span class="badge {{ $inboundReceipt->status->badgeClass() }} badge-sm">{{ $inboundReceipt->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $inboundReceipt->vendor->name }} · Nhận ngày {{ $inboundReceipt->received_date->format('d/m/Y') }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.inbound-receipts.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $inboundReceipt)
        <a href="{{ route('backend.inbound-receipts.edit', $inboundReceipt) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        @if($inboundReceipt->status->value === 'draft')
        <form method="POST" action="{{ route('backend.inbound-receipts.complete', $inboundReceipt) }}"
              onsubmit="return confirm('Hoàn tất phiếu nhập và tự động sinh tem truy vết QR cho toàn bộ lô hàng? Không thể hoàn tác.');">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">Hoàn tất & Sinh tem QR</button>
        </form>
        @endif
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6 items-start">

    <div class="space-y-6">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-4">Các lô hàng trong chuyến này</h2>

                @if($inboundReceipt->batches->isEmpty())
                <p class="text-sm text-base-content/50">Chưa có lô hàng nào được ghi nhận.</p>
                @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mã lô nội bộ</th>
                                <th>Mã lô NSX</th>
                                <th>Sản phẩm</th>
                                <th>SL nhập</th>
                                <th>Hạn dùng</th>
                                <th>Trạng thái</th>
                                <th>Tem QR</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inboundReceipt->batches as $batch)
                            <tr>
                                <td class="font-mono">
                                    <a href="{{ route('backend.batches.show', $batch) }}" class="link link-hover">{{ $batch->internal_batch_code }}</a>
                                </td>
                                <td class="font-mono text-xs">{{ $batch->mfg_batch_number ?? '—' }}</td>
                                <td>{{ $batch->product->name }}</td>
                                <td>{{ $batch->initial_qty }}</td>
                                <td>
                                    {{ $batch->exp_date->format('d/m/Y') }}
                                    @if($batch->isExpired())
                                    <span class="badge badge-error badge-xs ml-1">Hết hạn</span>
                                    @elseif($batch->isExpiringWithinDays(30))
                                    <span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>
                                    @endif
                                </td>
                                <td><span class="badge {{ $batch->status->badgeClass() }} badge-xs">{{ $batch->status->label() }}</span></td>
                                <td>{{ $batch->tags_count }}</td>
                                <td class="text-right">
                                    <a href="{{ route('backend.batches.show', $batch) }}" class="btn btn-ghost btn-xs">Xem</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-4">Chứng từ chuyến hàng</h2>

                @if($inboundReceipt->documents->isEmpty())
                <p class="text-sm text-base-content/50">Chưa có chứng từ nào được đính kèm.</p>
                @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Loại chứng từ</th>
                                <th>Số hiệu</th>
                                <th>File</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inboundReceipt->documents as $document)
                            <tr>
                                <td>{{ $document->document_code->label() }}</td>
                                <td class="font-mono">{{ $document->document_number }}</td>
                                <td>
                                    @if($document->file_url)
                                    <a href="{{ $document->file_url }}" target="_blank" class="link link-primary text-xs">Xem file</a>
                                    @else
                                    <span class="text-xs text-base-content/40">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @can('update', $inboundReceipt)
                                    <form method="POST" action="{{ route('backend.inbound-receipts.documents.destroy', [$inboundReceipt, $document]) }}"
                                          onsubmit="return confirm('Xóa chứng từ này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-xs text-error">Xóa</button>
                                    </form>
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

    </div>

    <div class="space-y-6">

        @if($inboundReceipt->notes)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-2">Ghi chú</h2>
                <p class="text-sm text-base-content/70 whitespace-pre-line">{{ $inboundReceipt->notes }}</p>
            </div>
        </div>
        @endif

        @can('update', $inboundReceipt)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thêm lô hàng mới</h2>

                @if($errors->any() && old('_form') === 'batch')
                <div class="alert alert-error py-2 px-3 mb-3 text-xs">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('backend.inbound-receipts.batches.store', $inboundReceipt) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="_form" value="batch">

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Sản phẩm (SKU)</span></label>
                        <select name="product_id" class="select select-bordered select-sm w-full">
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') === $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Mã lô nhà sản xuất</span></label>
                        <input type="text" name="mfg_batch_number" value="{{ old('mfg_batch_number') }}" placeholder="In trên bao bì" class="input input-bordered input-sm w-full">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày sản xuất</span></label>
                            <input type="date" name="mfg_date" value="{{ old('mfg_date') }}" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Hạn sử dụng</span></label>
                            <input type="date" name="exp_date" value="{{ old('exp_date') }}" class="input input-bordered input-sm w-full">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số lượng nhập</span></label>
                        <input type="number" name="initial_qty" value="{{ old('initial_qty') }}" min="1" class="input input-bordered input-sm w-full">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full">Tạo lô</button>
                </form>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thêm chứng từ chuyến hàng</h2>

                <div class="alert alert-warning py-2.5 px-3 mb-3 text-xs items-start">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <span><strong>Lưu ý:</strong> Các chứng từ tải lên tại đây (Hóa đơn VAT, Tờ khai hải quan, Vận đơn...) được bảo mật tuyệt đối dùng cho lưu hành nội bộ và kế toán. Khách hàng quét mã QR truy xuất sẽ KHÔNG nhìn thấy dữ liệu này.</span>
                </div>

                @if($errors->any() && old('_form') === 'document')
                <div class="alert alert-error py-2 px-3 mb-3 text-xs">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('backend.inbound-receipts.documents.store', $inboundReceipt) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="_form" value="document">

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại chứng từ</span></label>
                        <select name="document_code" class="select select-bordered select-sm w-full">
                            @foreach(\Modules\Warehouse\Enums\InboundDocumentType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('document_code') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số hiệu</span></label>
                        <input type="text" name="document_number" value="{{ old('document_number') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Đường dẫn file scan</span></label>
                        <input type="url" name="file_url" value="{{ old('file_url') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full">Thêm chứng từ</button>
                </form>
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection
