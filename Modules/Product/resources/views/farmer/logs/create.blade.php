@extends('layouts.mobile')
@section('title', 'Ghi Nhật Ký')
@section('subtitle', $farmingBatch->batch_code)
@section('back_url', auth()->user()->hasRole(\App\Enums\RoleEnum::FARMER->value) ? route('farmer.dashboard') : route('backend.farming-batches.show', $farmingBatch))

@section('content')

<div class="card bg-primary/5 border border-primary/20 shadow-sm mb-4">
    <div class="card-body p-3">
        <p class="text-sm font-semibold text-base-content">{{ $farmingBatch->agriSeed?->name ?? '—' }}</p>
        <p class="text-xs font-mono text-base-content/60 mt-0.5">{{ $farmingBatch->batch_code }}</p>
        <p class="text-xs text-base-content/50 mt-0.5">{{ $farmingBatch->farmingSource?->name ?? '—' }}</p>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error py-2.5 px-4 mb-4 text-sm">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div x-data="{
        steps: {{ Js::from($steps) }},
        selectedKey: '',
        get selectedStep() { return this.steps.find(s => s.key === this.selectedKey) ?? {}; },
        get activityType() { return this.selectedStep.activity_type ?? ''; },
        get vendorFarmingStepId() { return this.selectedStep.vendor_farming_step_id ?? null; },
        imagePreview: null,
        onImageChange(e) {
            const file = e.target.files[0];
            if (!file) { this.imagePreview = null; return; }
            this.imagePreview = URL.createObjectURL(file);
        },
    }">

    <form method="POST" action="{{ route('farmer.batches.log.store', $farmingBatch) }}" enctype="multipart/form-data" class="space-y-4" data-farming-log-form>
        @csrf

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <template x-for="step in steps" :key="step.key">
                <button type="button" @click="selectedKey = step.key"
                        :class="selectedKey === step.key ? step.color : 'btn-outline'"
                        class="btn btn-sm" x-text="step.label"></button>
            </template>
        </div>
        <input type="hidden" name="activity_type" :value="activityType">
        <input type="hidden" name="vendor_farming_step_id" :value="vendorFarmingStepId">

        <template x-if="!selectedKey">
            <p class="text-xs text-base-content/40 -mt-1">Chọn một công đoạn phía trên để tiếp tục.</p>
        </template>

        <template x-if="activityType === 'harvest' && '{{ $farmingBatch->pre_harvest_status }}' !== 'passed'">
            <div class="alert alert-warning py-2 px-3 text-xs">
                Lô này chưa được QC phê duyệt Readiness — hệ thống sẽ từ chối nếu bạn lưu nhật ký thu hoạch lúc này.
            </div>
        </template>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày giờ thực hiện <span class="text-error">*</span></span></label>
            <div class="flex gap-2">
                <input type="text" id="fp-activity_date" name="activity_date" value="{{ now()->format('Y-m-d H:i:s') }}"
                       class="input input-bordered w-full fp-init" data-fp-mode="datetime"
                       placeholder="dd/mm/yyyy hh:mm" required data-req="Vui lòng chọn ngày giờ thực hiện">
                <button type="button" id="activity-date-now-btn" class="btn btn-outline shrink-0">📍 Ngay bây giờ</button>
            </div>
        </div>

        <template x-if="activityType === 'fertilizer'">
            <div class="space-y-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Phân bón <span class="text-error">*</span></span></label>
                    <select id="ts-agri_fertilizer_id" name="agri_fertilizer_id" class="select select-bordered w-full ts-init"
                            data-ts-placeholder="— Chọn phân bón —"
                            x-init="$nextTick(() => window.initAllTomSelects?.($el.closest('form')))">
                        <option value="">— Chọn phân bón —</option>
                        @foreach($fertilizers as $fertilizer)
                        <option value="{{ $fertilizer->id }}">{{ $fertilizer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Số lượng</span></label>
                        <input type="number" step="0.01" name="quantity" class="input input-bordered w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đơn vị</span></label>
                        <input type="text" name="unit" placeholder="kg, lít..." class="input input-bordered w-full">
                    </div>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Cách bón</span></label>
                    <input type="text" name="method_or_target" placeholder="VD: bón lót, bón thúc" class="input input-bordered w-full">
                </div>
            </div>
        </template>

        <template x-if="activityType === 'pesticide'">
            <div class="space-y-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Thuốc BVTV <span class="text-error">*</span></span></label>
                    <select id="ts-agri_pesticide_id" name="agri_pesticide_id" class="select select-bordered w-full ts-init"
                            data-ts-placeholder="— Chọn thuốc BVTV —"
                            x-init="$nextTick(() => window.initAllTomSelects?.($el.closest('form')))">
                        <option value="">— Chọn thuốc BVTV —</option>
                        @foreach($pesticides as $pesticide)
                        <option value="{{ $pesticide->id }}">{{ $pesticide->trade_name }}@if($pesticide->target_pest) ({{ $pesticide->target_pest }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Liều lượng</span></label>
                        <input type="number" step="0.01" name="quantity" class="input input-bordered w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đơn vị</span></label>
                        <input type="text" name="unit" placeholder="ml, lít..." class="input input-bordered w-full">
                    </div>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đối tượng phòng trừ</span></label>
                    <input type="text" name="method_or_target" placeholder="VD: rệp, sâu vẽ bùa" class="input input-bordered w-full">
                </div>
            </div>
        </template>

        <template x-if="activityType === 'harvest'">
            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Số lượng thu hoạch</span></label>
                    <input type="number" step="0.01" name="quantity" class="input input-bordered w-full">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đơn vị</span></label>
                    <input type="text" name="unit" placeholder="kg, tấn..." class="input input-bordered w-full">
                </div>
            </div>
        </template>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ghi chú</span></label>
            <textarea name="notes" rows="2" class="textarea textarea-bordered w-full"></textarea>
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ảnh minh chứng</span></label>
            <label class="btn btn-outline btn-block gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Chụp ảnh minh chứng
                <input type="file" name="image" accept="image/*" capture="environment" class="hidden" @change="onImageChange($event)">
            </label>
            <template x-if="imagePreview">
                <img :src="imagePreview" class="mt-2 rounded-lg border border-base-200 max-h-48 object-contain">
            </template>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Lưu nhật ký</button>
    </form>
</div>

@endsection

@push('styles')
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
