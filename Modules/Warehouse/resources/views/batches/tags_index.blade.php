@extends('layouts.backend')
@section('title', 'Tem truy vết — ' . $batch->internal_batch_code)

@section('content')
<div x-data="retailItemTagListPage({{ Js::from([
    'apiUrl'    => route('backend.api.batches.tags', $batch),
    'canManage' => auth()->user()->can('update', $batch),
    'csrfToken' => csrf_token(),
]) }})">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content font-mono">{{ $batch->internal_batch_code }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $batch->product->name }} · {{ $tagsTotal }} tem truy vết</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.batches.show', $batch) }}" class="btn btn-ghost btn-sm">Quay lại lô hàng</a>
            <a href="{{ route('backend.batches.tags.print', $batch) }}" class="btn btn-primary btn-sm">In tem QR (PDF)</a>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="retail-item-tag-table"></div>
        </div>
    </div>

</div>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Warehouse/resources/assets/js/warehouse.js',
    ], 'build/backend')
@endpush
