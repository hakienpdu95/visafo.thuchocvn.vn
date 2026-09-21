@extends('layouts.backend')
@section('title', 'Chỉnh sửa sổ kiểm thực Bước 2')

@section('content')
@include('foodinspection::food-inspection-step2._form', ['log' => $log])
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
