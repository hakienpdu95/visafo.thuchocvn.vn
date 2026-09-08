@extends('layouts.backend')
@section('title', 'Chỉnh sửa sản phẩm')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa sản phẩm</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $product->sku }} · {{ $product->name }}</p>
    </div>
    <a href="{{ route('backend.products.show', $product) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.products.update', $product) }}" novalidate data-product-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Thông tin sản phẩm
                </h2>

                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên sản phẩm <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}"
                               data-req="Vui lòng nhập tên sản phẩm"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Sữa rửa mặt dịu nhẹ cho bé" autofocus>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã SKU <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                                   data-req="Vui lòng nhập mã SKU"
                                   data-val-maxlength="100"
                                   class="input input-bordered input-sm w-full font-mono uppercase @error('sku') input-error @enderror"
                                   placeholder="VD: SP-000123" maxlength="100">
                            @error('sku')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã vạch</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                            </label>
                            <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                                   data-val-maxlength="50"
                                   class="input input-bordered input-sm w-full font-mono @error('barcode') input-error @enderror"
                                   placeholder="VD: 8938501234567" maxlength="50">
                            @error('barcode')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Thương hiệu</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                            </label>
                            <select id="ts-brand_id" name="brand_id"
                                    class="select select-bordered select-sm w-full ts-init @error('brand_id') select-error @enderror"
                                    data-ts-placeholder="— Không có —">
                                <option value="">— Không có —</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            @error('brand_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Đơn vị tính <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="unit" value="{{ old('unit', $product->unit) }}"
                                   data-req="Vui lòng nhập đơn vị tính"
                                   data-val-maxlength="30"
                                   class="input input-bordered input-sm w-full @error('unit') input-error @enderror"
                                   placeholder="VD: Hộp, Bịch, Cái..." maxlength="30">
                            @error('unit')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ngành hàng <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-category_type" name="category_type"
                                class="select select-bordered select-sm w-full ts-init @error('category_type') select-error @enderror"
                                data-ts-placeholder="— Chọn ngành hàng —"
                                data-req="Vui lòng chọn ngành hàng">
                            @foreach(\Modules\Product\Enums\ProductCategoryType::cases() as $category)
                            <option value="{{ $category->value }}" @selected(old('category_type', $product->category_type->value) === $category->value)>{{ $category->label() }}</option>
                            @endforeach
                        </select>
                        @error('category_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

            </div>
        </div>

        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach(\Modules\Product\Enums\ProductStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $product->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $product->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $product->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.products.show', $product) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu thay đổi
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>
        </div>

    </div>
</form>
@endsection

@push('styles')
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
