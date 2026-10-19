@extends('layouts.backend')
@section('title', 'Thêm nhân viên')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm nhân viên mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Sau khi lưu, bạn có thể bổ sung hồ sơ y tế/ATTP cho nhân viên</p>
    </div>
    <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.employees.store') }}" enctype="multipart/form-data" novalidate data-employee-form>
    @csrf

    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Thông tin cá nhân</h2>

                @include('employee::employees.partials.profile-fields', ['employee' => null])

            </div>
        </div>
    </div>

    <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">Lưu nhân viên</button>
        <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    </div>

</form>
@endsection

@push('styles')
    @vite(['Modules/Employee/resources/assets/sass/employee.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/Employee/resources/assets/js/employee.js',
    ], 'build/backend')
@endpush
