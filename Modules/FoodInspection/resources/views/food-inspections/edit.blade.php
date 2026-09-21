@extends('layouts.backend')
@section('title', 'Chỉnh sửa sổ kiểm thực ' . $log->inspected_at?->format('d/m/Y H:i'))

@section('content')
@include('foodinspection::food-inspections._form', ['log' => $log])
@endsection

@push('styles')
    @vite(['Modules/FoodInspection/resources/assets/sass/foodinspection.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/FoodInspection/resources/assets/js/foodinspection.js',
    ], 'build/backend')
@endpush
