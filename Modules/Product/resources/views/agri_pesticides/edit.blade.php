@extends('layouts.backend')
@section('title', 'Chỉnh sửa thuốc BVTV')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $agriPesticide->trade_name }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $agriPesticide->category }}</p>
    </div>
    <a href="{{ route('backend.master-data.pesticides.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<div class="space-y-5">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-5">Thông tin chuẩn Bộ NN&amp;PTNT</h2>
            <p class="text-xs text-base-content/40 -mt-3 mb-4">Dữ liệu gốc từ danh mục thuốc BVTV — không thể chỉnh sửa tại đây</p>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Nhóm thuốc</dt>
                    <dd class="font-medium">{{ $agriPesticide->category }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Tên thương phẩm</dt>
                    <dd class="font-medium">{{ $agriPesticide->trade_name }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-base-content/40 mb-0.5">Thành phần hoạt chất</dt>
                    <dd class="font-medium">{{ $agriPesticide->active_ingredients }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Đối tượng phòng trừ</dt>
                    <dd class="font-medium">{{ $agriPesticide->target_pest ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Tổ chức đăng ký</dt>
                    <dd class="font-medium">{{ $agriPesticide->applicant ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <form method="POST" action="{{ route('backend.master-data.pesticides.update', $agriPesticide) }}" novalidate data-agri-pesticide-form>
        @csrf
        @method('PUT')

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Khuyến cáo sử dụng tại Visafo</h2>

                <div class="form-control sm:max-w-xs">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Thời gian cách ly (ngày)</span>
                        <span class="label-text-alt text-xs text-base-content/40">Theo khuyến cáo trên bao bì</span>
                    </label>
                    <input type="number" name="quarantine_days" min="0"
                           value="{{ old('quarantine_days', $agriPesticide->quarantine_days) }}"
                           data-val-pattern="^[0-9]*$"
                           class="input input-bordered input-sm w-full @error('quarantine_days') input-error @enderror"
                           placeholder="VD: 7">
                    @error('quarantine_days')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-base-content/40">Dùng để chặn thu hoạch/xuất hàng nếu chưa hết thời gian cách ly.</p>
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none group mt-4">
                    <input type="checkbox" name="is_banned" value="1"
                           class="checkbox checkbox-sm checkbox-error mt-0.5 shrink-0"
                           @checked(old('is_banned', $agriPesticide->is_banned))>
                    <div>
                        <span class="text-sm font-medium group-hover:text-error transition-colors">Đã bị cấm lưu hành</span>
                        <p class="text-xs text-base-content/50 mt-0.5">Bật cờ này nếu Bộ NN&amp;PTNT đã thu hồi/cấm lưu hành thuốc này — sẽ bị loại khỏi dropdown ghi nhật ký phun thuốc.</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
            <button type="submit" class="btn btn-primary btn-sm gap-1.5">Lưu thay đổi</button>
            <a href="{{ route('backend.master-data.pesticides.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
        </div>

    </form>
</div>
@endsection

@push('styles')
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
