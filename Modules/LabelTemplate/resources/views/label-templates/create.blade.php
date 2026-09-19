@extends('layouts.backend')
@section('title', 'Thêm mẫu tem')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm mẫu tem</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Khai báo mẫu tem in và đường dẫn Blade view tương ứng</p>
    </div>
    <a href="{{ route('backend.label-templates.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ route('backend.label-templates.store') }}" novalidate>
    @csrf
    @include('labeltemplate::label-templates._form')
</form>
@endsection
