@extends('layouts.backend')
@section('title', 'Kiểm thực ' . $log->inspected_at?->format('d/m/Y H:i'))

@section('content')
@php
    use Modules\FoodInspection\Enums\FoodGroup;

    $toRows = fn ($items) => $items->values()->map(fn ($d, $i) => [
        'id'                   => $d->id,
        'stt'                  => $i + 1,
        'product_name'         => $d->product_name,
        'received_at'          => $d->received_at?->format('d/m/Y H:i'),
        'quantity'             => $d->quantity !== null ? rtrim(rtrim((string) $d->quantity, '0'), '.') : null,
        'unit'                 => $d->unit,
        'vendor_name'          => $d->vendor_name,
        'supplier_address_phone' => $d->supplier_address_phone,
        'deliverer_name'       => $d->deliverer_name,
        'has_invoice'          => $d->has_invoice,
        'has_vet_cert'         => $d->has_vet_cert,
        'has_quarantine_cert'  => $d->has_quarantine_cert,
        'sensory_label'        => $d->sensory_result->label(),
        'sensory_badge'        => $d->sensory_result->badgeClass(),
        'quick_label'          => $d->quick_test_result->label(),
        'quick_badge'          => $d->quick_test_result->badgeClass(),
        'handling_measure'     => $d->handling_measure,
        'manufacturer_name'    => $d->manufacturer_name,
        'manufacturer_address' => $d->manufacturer_address,
        'expiry_date'          => $d->expiry_date?->format('d/m/Y'),
        'storage_condition'    => $d->storage_condition?->label(),
        'failed'               => $d->isFailed(),
    ])->all();

    $groups = [
        ['id' => 'fi-fresh-table', 'group' => 'fresh', 'group_enum' => FoodGroup::Fresh, 'rows' => $toRows($log->details->where('food_group', FoodGroup::Fresh))],
        ['id' => 'fi-dry-table',   'group' => 'dry',   'group_enum' => FoodGroup::Dry,   'rows' => $toRows($log->details->where('food_group', FoodGroup::Dry))],
    ];
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Kiểm thực Bước 1 — {{ $log->inspected_at?->format('d/m/Y H:i') }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $log->inspection_location ?: '—' }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.food-inspections.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>

        @can('update', $log)
        <a href="{{ route('backend.food-inspections.edit', $log) }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Chỉnh sửa
        </a>
        @endcan

        @can('delete', $log)
        <button type="button" class="btn btn-error btn-sm gap-1.5" onclick="document.getElementById('fiDeleteModal').showModal()">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Xóa
        </button>
        @endcan
    </div>
</div>

<div class="flex flex-col gap-6">
    <div class="card w-full bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-4">Thông tin chung</p>
            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-4 text-sm">
                <div><dt class="text-xs text-base-content/50 mb-0.5">Người kiểm tra</dt><dd class="font-medium">{{ $log->inspector?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Địa điểm kiểm tra</dt><dd>{{ $log->inspection_location ?: '—' }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Khách hàng / Điểm phục vụ</dt><dd class="font-medium">{{ $log->customer_name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Tạo lúc</dt><dd>{{ $log->created_at?->format('d/m/Y H:i') }}</dd></div>
                <div>
                    <dt class="text-xs text-base-content/50 mb-0.5">Sửa lần cuối</dt>
                    <dd>{{ $log->updated_at?->format('d/m/Y H:i') }}@if($log->updated_at?->gt($log->created_at?->addSecond())) <span class="badge badge-warning badge-soft badge-xs ml-1">Đã chỉnh sửa</span>@endif</dd>
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <dt class="text-xs text-base-content/50 mb-0.5">Chứng từ đính kèm</dt>
                    <dd class="flex flex-wrap gap-2">
                        @forelse($log->attachments ?? [] as $file)
                            <a href="{{ Storage::disk('public')->url($file['path']) }}" target="_blank" rel="noopener" class="link link-primary text-xs">{{ $file['name'] }}</a>
                        @empty
                            <span>—</span>
                        @endforelse
                    </dd>
                </div>
                @if($log->note)
                <div class="md:col-span-2 lg:col-span-3"><dt class="text-xs text-base-content/50 mb-0.5">Ghi chú</dt><dd>{{ $log->note }}</dd></div>
                @endif
            </dl>
        </div>
    </div>

    @foreach($groups as $g)
    <div>
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">{{ $g['group_enum']->label() }} ({{ count($g['rows']) }})</p>
        <div class="section-page">
            <div class="card">
                <div class="card-body p-0 overflow-hidden tabulator-daisy">
                    <div id="{{ $g['id'] }}" data-fi-table data-group="{{ $g['group'] }}" data-rows="{{ json_encode($g['rows'], JSON_UNESCAPED_UNICODE) }}"></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>

@can('delete', $log)
<dialog id="fiDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa sổ kiểm thực
            <strong class="text-base-content">{{ $log->inspected_at?->format('d/m/Y H:i') }}</strong>
            cùng toàn bộ {{ $log->details->count() }} dòng hàng?
        </p>
        <p class="text-xs text-error/70">Thao tác này không thể hoàn tác từ giao diện.</p>
        <form method="POST" action="{{ route('backend.food-inspections.destroy', $log) }}" class="modal-action mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-error btn-sm">Xóa</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('fiDeleteModal').close()">Hủy</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/FoodInspection/resources/assets/sass/foodinspection.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/FoodInspection/resources/assets/js/foodinspection.js',
    ], 'build/backend')
@endpush
