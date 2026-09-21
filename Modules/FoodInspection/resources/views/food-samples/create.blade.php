@extends('layouts.backend')
@section('title', 'Lưu mẫu thức ăn')

@section('content')
@include('foodinspection::food-samples._form', ['log' => null])
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
