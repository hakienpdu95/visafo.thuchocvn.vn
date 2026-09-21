@extends('layouts.backend')
@section('title', 'Kiểm thực Bước 3 ' . $log->inspection_date?->format('d/m/Y'))

@section('content')
@php
    $toMinutes = fn (?string $t) => $t ? ((int) substr($t, 0, 2)) * 60 + (int) substr($t, 3, 2) : null;
    $rows = $log->details->values()->map(fn ($d, $i) => [
        'id'             => $d->id,
        'stt'            => $i + 1,
        'meal_label'     => $d->meal_time->label(),
        'dish_name'      => $d->dish_name,
        'source'         => $d->step2_detail_id ? 'step2' : ($d->menu_dish_id ? 'menu' : null),
        'quantity'       => $d->quantity,
        'portion_time'   => $d->portion_time ? substr($d->portion_time, 0, 5) : null,
        'eat_time'       => $d->eat_time ? substr($d->eat_time, 0, 5) : null,
        // Thời gian phơi nhiễm: từ lúc chia xong đến lúc bắt đầu ăn.
        'wait_minutes'   => ($toMinutes($d->portion_time) !== null && $toMinutes($d->eat_time) !== null) ? $toMinutes($d->eat_time) - $toMinutes($d->portion_time) : null,
        'equipment_used' => $d->equipment_used,
        'sensory_eval'   => $d->sensory_eval,
        'action_taken'   => $d->action_taken,
        'failed'         => $d->isFailed(),
    ])->all();
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Kiểm thực Bước 3 — {{ $log->inspection_date?->format('d/m/Y') }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $log->customer_name }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.food-inspection-step3.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>

        @can('update', $log)
        <a href="{{ route('backend.food-inspection-step3.edit', $log) }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Chỉnh sửa
        </a>
        @endcan

        @can('delete', $log)
        <button type="button" class="btn btn-error btn-sm gap-1.5" onclick="document.getElementById('fiStep3DeleteModal').showModal()">
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
                <div><dt class="text-xs text-base-content/50 mb-0.5">Tên cơ sở / Doanh nghiệp suất ăn</dt><dd class="font-medium">{{ $log->customer_name }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Địa điểm</dt><dd>{{ $log->location_name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Ngày kiểm tra</dt><dd>{{ $log->inspection_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Người kiểm tra</dt><dd class="font-medium">{{ $log->inspector_name }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Tạo lúc</dt><dd>{{ $log->created_at?->format('d/m/Y H:i') }}</dd></div>
                <div>
                    <dt class="text-xs text-base-content/50 mb-0.5">Sửa lần cuối</dt>
                    <dd>{{ $log->updated_at?->format('d/m/Y H:i') }}@if($log->updated_at?->gt($log->created_at?->addSecond())) <span class="badge badge-warning badge-soft badge-xs ml-1">Đã chỉnh sửa</span>@endif</dd>
                </div>
                @if($log->note)
                <div class="md:col-span-2 lg:col-span-3"><dt class="text-xs text-base-content/50 mb-0.5">Ghi chú</dt><dd>{{ $log->note }}</dd></div>
                @endif
            </dl>
        </div>
    </div>

    <div>
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Kiểm tra trước khi ăn ({{ count($rows) }} món)</p>
        <div class="section-page">
            <div class="card">
                <div class="card-body p-0 overflow-hidden tabulator-daisy">
                    <div id="step3-detail-table" data-rows="{{ json_encode($rows, JSON_UNESCAPED_UNICODE) }}"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@can('delete', $log)
<dialog id="fiStep3DeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa sổ kiểm thực Bước 3 ngày <strong class="text-base-content">{{ $log->inspection_date?->format('d/m/Y') }}</strong>
            của {{ $log->customer_name }} cùng {{ count($rows) }} dòng món ăn?
        </p>
        <form method="POST" action="{{ route('backend.food-inspection-step3.destroy', $log) }}" class="modal-action mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-error btn-sm">Xóa</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('fiStep3DeleteModal').close()">Hủy</button>
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
