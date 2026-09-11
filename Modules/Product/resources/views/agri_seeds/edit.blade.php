@extends('layouts.backend')
@section('title', 'Chỉnh sửa giống cây trồng')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $agriSeed->name }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $agriSeed->crop_type }}</p>
    </div>
    <a href="{{ route('backend.master-data.seeds.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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
            <h2 class="card-title text-base mb-5">Thông tin chuẩn Danh mục Quốc gia</h2>
            <p class="text-xs text-base-content/40 -mt-3 mb-4">Dữ liệu gốc từ danh mục giống cây trồng — không thể chỉnh sửa tại đây</p>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Loại cây trồng</dt>
                    <dd class="font-medium">{{ $agriSeed->crop_type }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Tên giống</dt>
                    <dd class="font-medium">{{ $agriSeed->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Tác giả / Cơ quan đăng ký</dt>
                    <dd class="font-medium">{{ $agriSeed->author_applicant ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Số Quyết định / Năm công nhận</dt>
                    <dd class="font-medium">{{ $agriSeed->decision_number ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <form method="POST" action="{{ route('backend.master-data.seeds.update', $agriSeed) }}" novalidate data-agri-seed-form>
        @csrf
        @method('PUT')

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Trạng thái lưu hành tại Visafo</h2>

                <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                    <input type="checkbox" name="is_banned" value="1"
                           class="checkbox checkbox-sm checkbox-error mt-0.5 shrink-0"
                           @checked(old('is_banned', $agriSeed->is_banned))>
                    <div>
                        <span class="text-sm font-medium group-hover:text-error transition-colors">Đã bị loại khỏi danh mục</span>
                        <p class="text-xs text-base-content/50 mt-0.5">Bật cờ này nếu giống đã bị thu hồi/loại khỏi Danh mục Giống cây trồng Quốc gia — sẽ bị loại khỏi dropdown chọn giống khi mở Vụ/Lô sản xuất.</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
            <button type="submit" class="btn btn-primary btn-sm gap-1.5">Lưu thay đổi</button>
            <a href="{{ route('backend.master-data.seeds.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
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
