@extends('layouts.backend')
@section('title', 'Thêm thương hiệu mới')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm thương hiệu mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tên, logo và mô tả ngắn của thương hiệu</p>
    </div>
    <a href="{{ route('backend.brands.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.brands.store') }}" novalidate data-brand-form>
    @csrf

    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 12a8 8 0 11-16 0 8 8 0 0116 0zM12 8v4l2.5 2.5"/>
                    </svg>
                    Thông tin thương hiệu
                </h2>

                <div class="form-control mb-4">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Logo</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn — JPG, PNG, WEBP, tối đa 5MB</span>
                    </label>
                    <input type="hidden" name="logo_media_uuid" data-logo-uuid>
                    <input type="file" name="file" data-logo-pond>
                </div>

                <div class="form-control mb-4">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên thương hiệu <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           data-req="Vui lòng nhập tên thương hiệu"
                           class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                           placeholder="VD: Johnson's Baby" autofocus>
                    @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                    </label>
                    <textarea name="description" rows="4"
                              data-val-maxlength="1000"
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                              placeholder="Giới thiệu ngắn gọn về thương hiệu...">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>
    </div>

    <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">Tạo thương hiệu</button>
        <a href="{{ route('backend.brands.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    </div>

</form>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/filepond.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
