@extends('layouts.backend')
@section('title', 'Lưu mẫu thức ăn ' . $log->sample_date?->format('d/m/Y'))

@section('content')
@php
    use Modules\FoodInspection\Enums\SampleStatus;

    $now = now();
    $rows = $log->details->values()->map(function ($d, $i) use ($now, $log) {
        $from = $d->destroyableFrom();
        $state = $d->isDestroyed() ? 'destroyed' : ($d->isDestroyable($now) ? 'pending' : 'stored');

        return [
            'id'             => $d->id,
            'stt'            => $i + 1,
            'meal_label'     => $d->meal_time->label(),
            'dish_name'      => $d->dish_name,
            'portion_qty'    => $d->portion_qty,
            'sample_volume'  => $d->sample_volume,
            'container_type' => $d->container_type,
            'storage_temp'   => $d->storage_temp !== null ? rtrim(rtrim((string) $d->storage_temp, '0'), '.') : null,
            'sampled_at'     => $d->sampled_at?->format('d/m/Y H:i'),
            'sampler_name'   => $d->sampler_name,
            'destroyable_from' => $from->format('d/m/Y H:i'),
            'state'          => $state,
            'remaining'      => $state === 'stored' ? $now->diffInMinutes($from) : null,
            'destroyed_at'   => $d->destroyed_at?->format('d/m/Y H:i'),
            'destroyer_name' => $d->destroyer_name,
            'quality_note'   => $d->quality_note,
            'label_url'      => route('backend.food-samples.labels', ['food_sample' => $log, 'detail' => $d->id]),
        ];
    })->all();

    $hasDestroyed = $log->details->contains(fn ($d) => $d->isDestroyed());
    $pendingCount = $log->details->filter(fn ($d) => $d->isDestroyable($now))->count();
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Lưu mẫu thức ăn — {{ $log->sample_date?->format('d/m/Y') }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $log->customer_name }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('backend.food-samples.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>

        <a href="{{ route('backend.food-samples.labels', $log) }}" target="_blank" rel="noopener" class="btn btn-outline btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
            In tem lưu mẫu
        </a>

        @can('update', $log)
        <a href="{{ route('backend.food-samples.edit', $log) }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            {{ $pendingCount > 0 ? 'Hủy mẫu / Chỉnh sửa' : 'Chỉnh sửa' }}
        </a>
        @endcan

        @can('delete', $log)
        @unless($hasDestroyed)
        <button type="button" class="btn btn-error btn-sm gap-1.5" onclick="document.getElementById('fiSampleDeleteModal').showModal()">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Xóa
        </button>
        @endunless
        @endcan
    </div>
</div>

@if($pendingCount > 0 && $log->status !== SampleStatus::Destroyed)
<div class="alert alert-warning py-2.5 px-4 mb-5 text-sm">
    Có <strong>{{ $pendingCount }}</strong> mẫu đã đủ 24 giờ lưu mẫu — đang chờ hủy. Bấm "Hủy mẫu / Chỉnh sửa" để ghi nhận giờ hủy và chất lượng mẫu.
</div>
@endif

<div class="flex flex-col gap-6">
    <div class="card w-full bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-4">Thông tin chung</p>
            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-4 text-sm">
                <div><dt class="text-xs text-base-content/50 mb-0.5">Khách hàng / Điểm phục vụ</dt><dd class="font-medium">{{ $log->customer_name }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Địa điểm</dt><dd>{{ $log->location_name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Ngày lưu mẫu</dt><dd>{{ $log->sample_date?->format('d/m/Y') }}</dd></div>
                <div>
                    <dt class="text-xs text-base-content/50 mb-0.5">Trạng thái</dt>
                    <dd>
                        @if($log->status === SampleStatus::Destroyed)
                            <span class="badge badge-neutral badge-soft badge-sm">Đã hủy</span>
                        @elseif($pendingCount > 0)
                            <span class="badge badge-warning badge-soft badge-sm">Chờ hủy mẫu</span>
                        @else
                            <span class="badge badge-info badge-soft badge-sm">Lưu mẫu</span>
                        @endif
                    </dd>
                </div>
                <div><dt class="text-xs text-base-content/50 mb-0.5">Người lập phiếu</dt><dd class="font-medium">{{ $log->creator_name }}</dd></div>
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
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Mẫu thức ăn lưu ({{ count($rows) }} mẫu)</p>
        <div class="section-page">
            <div class="card">
                <div class="card-body p-0 overflow-hidden tabulator-daisy">
                    <div id="food-sample-detail-table" data-rows="{{ json_encode($rows, JSON_UNESCAPED_UNICODE) }}"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@can('delete', $log)
@unless($hasDestroyed)
<dialog id="fiSampleDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa phiếu lưu mẫu ngày <strong class="text-base-content">{{ $log->sample_date?->format('d/m/Y') }}</strong>
            của {{ $log->customer_name }} cùng {{ count($rows) }} mẫu?
        </p>
        <form method="POST" action="{{ route('backend.food-samples.destroy', $log) }}" class="modal-action mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-error btn-sm">Xóa</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('fiSampleDeleteModal').close()">Hủy</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endunless
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
