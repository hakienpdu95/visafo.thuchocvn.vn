@extends('layouts.backend')
@section('title', 'Tạo thực đơn')

@section('content')
@include('menu::menus._form', ['menu' => null])
@endsection

@push('styles')
    @vite(['Modules/Menu/resources/assets/sass/menu.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Menu/resources/assets/js/menu.js',
    ], 'build/backend')
@endpush
