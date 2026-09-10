@extends('layouts.backend')
@section('title', 'Chỉnh sửa nhóm hàng')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa nhóm hàng</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $category->name }}</p>
    </div>
    <a href="{{ route('backend.categories.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

@php($canEditCode = auth()->user()->can('editCode', \Modules\Product\Models\Category::class))

<form method="POST" action="{{ route('backend.categories.update', $category) }}" novalidate data-category-form class="max-w-xl">
    @csrf
    @method('PUT')

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body space-y-4">

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Mã nhóm (code) <span class="text-error">*</span></span>
                    @if(!$canEditCode)
                    <span class="label-text-alt text-xs text-warning">Chỉ System Admin được sửa</span>
                    @endif
                </label>
                <input type="text" name="code" value="{{ old('code', $category->code) }}"
                       @if(!$canEditCode) readonly @endif
                       data-req="Vui lòng nhập mã nhóm hàng"
                       class="input input-bordered input-sm w-full font-mono @if(!$canEditCode) bg-base-200 cursor-not-allowed @endif @error('code') input-error @enderror">
                @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Tên nhóm <span class="text-error">*</span></span>
                </label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}"
                       data-req="Vui lòng nhập tên nhóm hàng"
                       class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                       autofocus>
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Mô tả</span>
                    <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                </label>
                <textarea name="description" rows="2"
                          class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $category->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-2 py-0">
                    <input type="checkbox" name="is_active" value="1" class="checkbox checkbox-sm" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                    <span class="label-text">Đang sử dụng</span>
                </label>
            </div>

            <div class="flex gap-2 pt-2">
                <a href="{{ route('backend.categories.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                <button type="submit" class="btn btn-primary btn-sm flex-1">Lưu thay đổi</button>
            </div>

        </div>
    </div>
</form>
@endsection
