@extends('layouts.mobile')
@section('title', 'Sửa Nhật Ký')
@section('subtitle', $farmingBatch->batch_code)
@section('back_url', auth()->user()->hasRole(\App\Enums\RoleEnum::FARMER->value) ? route('farmer.dashboard') : route('backend.farming-batches.show', $farmingBatch))

@section('content')

@include('product::farmer.logs.partials.form', [
    'action'      => route('farmer.logs.update', $farmingLog),
    'submitLabel' => 'Lưu thay đổi',
])

@endsection

@push('styles')
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
