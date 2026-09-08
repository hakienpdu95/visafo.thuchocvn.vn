@extends('layouts.backend')
@section('title', 'Khởi tạo thu hồi')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Khởi tạo chiến dịch thu hồi</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Để trống "Lô cụ thể" nếu thu hồi toàn bộ SKU</p>
    </div>
    <a href="{{ route('backend.product-recalls.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.product-recalls.store') }}" novalidate>
    @csrf

    <div class="card bg-base-100 shadow-sm border border-base-200 max-w-2xl">
        <div class="card-body space-y-4">

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Sản phẩm (SKU) <span class="text-error">*</span></span></label>
                <select name="product_id" id="product_id" class="select select-bordered select-sm w-full @error('product_id') select-error @enderror">
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(old('product_id') === $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
                @error('product_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Lô cụ thể (tùy chọn)</span></label>
                <input type="text" name="batch_id" value="{{ old('batch_id') }}" placeholder="Dán ID lô hàng nếu chỉ thu hồi 1 lô — xem tại trang chi tiết lô"
                       class="input input-bordered input-sm w-full font-mono @error('batch_id') input-error @enderror">
                @error('batch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mức độ nghiêm trọng</span></label>
                <select name="severity" class="select select-bordered select-sm w-full @error('severity') select-error @enderror">
                    <option value="">— Không xác định —</option>
                    @foreach(\Modules\Recall\Enums\RecallSeverity::cases() as $severity)
                    <option value="{{ $severity->value }}" @selected(old('severity') === $severity->value)>{{ $severity->label() }}</option>
                    @endforeach
                </select>
                @error('severity')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Lý do thu hồi <span class="text-error">*</span></span></label>
                <textarea name="reason" rows="3" class="textarea textarea-bordered textarea-sm w-full @error('reason') textarea-error @enderror">{{ old('reason') }}</textarea>
                @error('reason')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ghi chú</span></label>
                <textarea name="notes" rows="2" class="textarea textarea-bordered textarea-sm w-full @error('notes') textarea-error @enderror">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

        </div>
        <div class="card-body pt-0 flex-row justify-end gap-2 border-t border-base-200">
            <a href="{{ route('backend.product-recalls.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-error btn-sm" onclick="return confirm('Khởi tạo thu hồi sẽ tự động chuyển trạng thái lô hàng liên quan sang \'Đã thu hồi\' và khóa các tem QR còn trên kệ. Tiếp tục?');">Khởi tạo thu hồi</button>
        </div>
    </div>
</form>
@endsection
