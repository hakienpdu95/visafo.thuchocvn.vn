@extends('layouts.backend')
@section('title', 'Thêm hàng hóa NCC')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm hàng hóa NCC</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Kê khai mặt hàng thực tế của nhà cung cấp và ánh xạ về danh mục chuẩn Visafo</p>
    </div>
    <a href="{{ route('backend.partner-products.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.partner-products.store') }}" novalidate data-partner-product-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        <div class="space-y-6">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h18v4H3V3zm2 4h14v14H5V7zm3 4h8m-8 4h5"/>
                        </svg>
                        Nhà cung cấp trực tiếp (Tier 1)
                    </h2>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhà cung cấp <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-vendor_id" name="vendor_id"
                                class="select select-bordered select-sm w-full ts-init @error('vendor_id') select-error @enderror"
                                data-ts-placeholder="— Chọn nhà cung cấp —"
                                data-req="Vui lòng chọn nhà cung cấp">
                            <option value="">— Chọn nhà cung cấp —</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(old('vendor_id') === $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-base-content/40">Pháp nhân trực tiếp ký hợp đồng và xuất hóa đơn cho Visafo</p>
                    </div>

                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Hàng hóa NCC kê khai
                    </h2>

                    <div class="space-y-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên hàng (theo NCC) <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   data-req="Vui lòng nhập tên hàng"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Nạc mông, Ba chỉ, Sườn non" autofocus>
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã hàng của NCC</span>
                            </label>
                            <input type="text" value="MHCC-000001" disabled
                                   class="input input-bordered input-sm w-full font-mono text-base-content/40 bg-base-200/60">
                            <p class="mt-1 text-xs text-base-content/40">Hệ thống tự động sinh mã khi lưu, không thể chỉnh sửa tại đây.</p>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ánh xạ với Sản phẩm chuẩn của Visafo <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-product_id" name="product_id"
                                    class="select select-bordered select-sm w-full ts-init @error('product_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn sản phẩm chuẩn —"
                                    data-req="Vui lòng ánh xạ với sản phẩm chuẩn">
                                <option value="">— Chọn sản phẩm chuẩn —</option>
                                @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') === $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                            @error('product_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-base-content/40">Quyết định nhóm hàng và giấy tờ pháp lý bắt buộc theo Rule Engine</p>
                        </div>

                    </div>

                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-1">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Nhà sản xuất / Nguồn gốc thực tế
                        <span class="text-xs font-normal text-base-content/40">(Nếu mua qua trung gian)</span>
                    </h2>
                    <p class="text-xs text-base-content/40 mb-4">
                        VD: Mua thịt qua Thành Huân (Tier 1 — nhà cung cấp trực tiếp) nhưng nguồn gốc thực tế là lò mổ CP Việt Nam (Tier 2).
                        Giấy ISO/OCOP/kiểm dịch của nhà sản xuất gốc được đính kèm ngay trong trang chi tiết hàng hóa này, tách biệt khỏi hồ sơ pháp lý của {{ 'Tier 1' }}.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nhà sản xuất / Nguồn gốc</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                            </label>
                            <input type="text" name="manufacturer_name" value="{{ old('manufacturer_name') }}"
                                   data-val-maxlength="255"
                                   class="input input-bordered input-sm w-full @error('manufacturer_name') input-error @enderror"
                                   placeholder="VD: Công ty CP Chăn nuôi CP Việt Nam" maxlength="255">
                            @error('manufacturer_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Địa chỉ / Vùng trồng / Lò mổ gốc</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                            </label>
                            <input type="text" name="origin_address" value="{{ old('origin_address') }}"
                                   data-val-maxlength="500"
                                   class="input input-bordered input-sm w-full @error('origin_address') input-error @enderror"
                                   placeholder="VD: Nhà máy giết mổ CP Hà Nam" maxlength="500">
                            @error('origin_address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                </div>
            </div>

        </div>

        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach(\Modules\Product\Enums\PartnerProductStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', 'active') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.partner-products.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo mới
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
