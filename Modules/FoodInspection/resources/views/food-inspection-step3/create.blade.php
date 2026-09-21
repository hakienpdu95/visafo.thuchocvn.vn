@extends('layouts.backend')
@section('title', 'Sổ kiểm thực Bước 3')

@section('content')
@include('foodinspection::food-inspection-step3._form', ['log' => null])
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
