@php
    $log = $farmingLog ?? null;
    $productName = $farmingBatch->partnerProduct?->name ?? \Illuminate\Support\Str::limit((string) $farmingBatch->agriSeed?->name, 40) ?: 'Lô ' . $farmingBatch->batch_code;
    $oldStepId = old('vendor_farming_step_id');
    $initialKey = old('activity_type')
        ? ($oldStepId ? 'custom_' . $oldStepId : old('activity_type'))
        : ($currentKey ?? '');
@endphp

<div x-data="{
        steps: {{ Js::from($steps) }},
        materials: {{ Js::from($materials) }},
        selectedKey: {{ Js::from($initialKey) }},
        isolationDays: {{ Js::from(old('isolation_days', $log?->isolation_days ?? $log?->agriPesticide?->quarantine_days)) }},
        get selectedStep() { return this.steps.find(s => s.key === this.selectedKey) ?? {}; },
        get activityType() { return this.selectedStep.activity_type ?? ''; },
        get vendorFarmingStepId() { return this.selectedStep.vendor_farming_step_id ?? null; },
        get usesMaterial() { return ['fertilizer', 'pesticide'].includes(this.activityType); },
        get materialOptions() { return this.materials[this.activityType] ?? []; },
        onMaterialInput(e) {
            const name = e.target.value.trim().toLowerCase();
            const match = this.materialOptions.find(m => m.name.toLowerCase() === name);
            if (match && match.quarantine_days !== null && (this.isolationDays === null || this.isolationDays === '')) {
                this.isolationDays = match.quarantine_days;
            }
        },
        imagePreview: null,
        existingImage: {{ Js::from($log?->image_path ? \Illuminate\Support\Facades\Storage::url($log->image_path) : null) }},
        get shownImage() { return this.imagePreview || this.existingImage; },
        onImageChange(e) {
            const file = e.target.files[0];
            if (!file) { this.imagePreview = null; return; }
            this.imagePreview = URL.createObjectURL(file);
        },
    }">

    <div class="card bg-base-100 shadow-sm rounded-2xl mb-4">
        <div class="card-body p-4 flex-row items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-success/10 text-success flex items-center justify-center text-xl shrink-0">🌱</div>
            <div class="min-w-0">
                <p class="font-semibold text-base text-base-content truncate">{{ $productName }}</p>
                <p class="text-xs text-base-content/50 truncate">
                    <span class="font-mono">{{ $farmingBatch->batch_code }}</span>
                    @if($farmingBatch->farmingSource?->name) · {{ $farmingBatch->farmingSource->name }}@endif
                    @if($farmingBatch->sowing_date) · gieo {{ $farmingBatch->sowing_date->format('d/m/Y') }}@endif
                </p>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-error rounded-2xl py-2.5 px-4 mb-4 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4" data-farming-log-form>
        @csrf
        @if($log)
        @method('PUT')
        @endif

        <section class="card bg-base-100 shadow-sm rounded-2xl">
            <div class="card-body p-4 gap-3">
                <h2 class="text-sm font-semibold">Hoạt động <span class="text-error">*</span></h2>
                <div class="grid grid-cols-2 gap-3">
                    <template x-for="step in steps" :key="step.key">
                        <button type="button" @click="selectedKey = step.key"
                                :class="selectedKey === step.key ? step.color + ' shadow-md' : 'btn-outline border-base-300 bg-base-100 font-normal'"
                                class="btn h-14 rounded-xl text-sm leading-tight justify-center gap-1.5 px-2">
                            <span x-show="selectedKey === step.key" class="text-base">✓</span>
                            <span x-text="step.label"></span>
                        </button>
                    </template>
                </div>
                <input type="hidden" name="activity_type" :value="activityType">
                <input type="hidden" name="vendor_farming_step_id" :value="vendorFarmingStepId">

                <template x-if="!selectedKey">
                    <p class="text-xs text-base-content/40">Chọn một hoạt động để tiếp tục.</p>
                </template>

                @if($quarantineDaysLeft > 0)
                <template x-if="activityType === 'harvest'">
                    <div class="alert alert-error rounded-xl py-2 px-3 text-sm">
                        Lô đang trong thời gian cách ly thuốc (Còn {{ $quarantineDaysLeft }} ngày). Không được phép thu hoạch.
                    </div>
                </template>
                @elseif($farmingBatch->pre_harvest_status !== 'passed')
                <template x-if="activityType === 'harvest'">
                    <div class="alert alert-warning rounded-xl py-2 px-3 text-sm">
                        Lô này chưa được QC phê duyệt Readiness — hệ thống sẽ từ chối nếu bạn lưu nhật ký thu hoạch lúc này.
                    </div>
                </template>
                @endif
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm rounded-2xl">
            <div class="card-body p-4 gap-4">
                <h2 class="text-sm font-semibold">Chi tiết thực hiện</h2>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày giờ thực hiện <span class="text-error">*</span></span></label>
                    <div class="flex gap-2">
                        <input type="text" id="fp-activity_date" name="activity_date" value="{{ old('activity_date', $log?->activity_date->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s')) }}"
                               class="input input-bordered rounded-xl w-full fp-init" data-fp-mode="datetime"
                               placeholder="dd/mm/yyyy hh:mm" required data-req="Vui lòng chọn ngày giờ thực hiện">
                        <button type="button" id="activity-date-now-btn" class="btn btn-outline rounded-xl shrink-0">📍 Bây giờ</button>
                    </div>
                </div>

                <template x-if="usesMaterial">
                    <div class="space-y-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Loại vật tư sử dụng <span class="text-error">*</span></span></label>
                            <input type="text" name="material_name" value="{{ old('material_name', $log?->materialText()) }}"
                                   :list="'materials-' + activityType" autocomplete="off" @input="onMaterialInput($event)"
                                   :placeholder="activityType === 'pesticide' ? 'VD: Pesieu 500SC, Daconil 75WP' : 'VD: Phân gà, NPK 16-16-8'"
                                   class="input input-bordered rounded-xl w-full" required data-req="Vui lòng nhập loại vật tư sử dụng">
                            <datalist :id="'materials-' + activityType">
                                <template x-for="m in materialOptions" :key="m.name">
                                    <option :value="m.name"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Liều lượng <span class="text-error">*</span></span></label>
                                <input type="text" name="dosage" value="{{ old('dosage', $log?->dosageText()) }}"
                                       placeholder="VD: 200kg" class="input input-bordered rounded-xl w-full"
                                       required data-req="Vui lòng nhập liều lượng sử dụng">
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Cách ly (ngày)</span></label>
                                <input type="number" name="isolation_days" min="0" max="365" step="1" inputmode="numeric"
                                       x-model="isolationDays" placeholder="Theo bao bì" class="input input-bordered rounded-xl w-full">
                            </div>
                        </div>
                        <template x-if="activityType === 'pesticide'">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đối tượng phòng trừ</span></label>
                                <input type="text" name="method_or_target" value="{{ old('method_or_target', $log?->method_or_target) }}"
                                       placeholder="VD: rệp, sâu vẽ bùa" class="input input-bordered rounded-xl w-full">
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="activityType === 'harvest'">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Sản lượng <span class="text-error">*</span></span></label>
                            <input type="text" name="harvest_yield" value="{{ old('harvest_yield', $log?->harvestYieldText()) }}"
                                   placeholder="VD: 500 kg" class="input input-bordered rounded-xl w-full"
                                   required data-req="Vui lòng nhập sản lượng thu hoạch">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Cơ sở tiêu thụ</span></label>
                            <input type="text" name="buyer_name" value="{{ old('buyer_name', $log?->buyer_name) }}"
                                   class="input input-bordered rounded-xl w-full">
                        </div>
                    </div>
                </template>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Phương pháp thực hiện / Ghi chú</span></label>
                    <textarea name="notes" rows="3" maxlength="500" placeholder="Mô tả cách làm" class="textarea textarea-bordered rounded-xl w-full">{{ old('notes', $log?->notes) }}</textarea>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Người thực hiện</span></label>
                    <input type="text" name="operator_name" value="{{ old('operator_name', $log?->operatorText() ?? auth()->user()->name) }}"
                           maxlength="150" class="input input-bordered rounded-xl w-full">
                </div>
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm rounded-2xl">
            <div class="card-body p-4 gap-3">
                <h2 class="text-sm font-semibold">Ảnh minh chứng</h2>
                <label class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-base-300 bg-base-200/40 p-5 text-center cursor-pointer active:bg-base-200 transition-colors"
                       :class="shownImage ? 'border-primary/50' : ''">
                    <template x-if="shownImage">
                        <img :src="shownImage" class="rounded-xl max-h-56 object-contain">
                    </template>
                    <svg x-show="!shownImage" class="w-10 h-10 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-sm font-medium" x-text="shownImage ? 'Bấm để chụp lại / đổi ảnh khác' : 'Bấm để chụp hoặc tải ảnh lên'"></span>
                    <span class="text-xs text-base-content/40">Vỏ thuốc, bao phân, hiện trường — JPG/PNG tối đa 10MB</span>
                    <input type="file" name="image" accept="image/*" capture="environment" class="hidden" @change="onImageChange($event)">
                </label>
            </div>
        </section>

        <div class="fixed bottom-0 inset-x-0 z-50 pointer-events-none">
            <div class="max-w-lg mx-auto bg-base-100 border-t border-base-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pointer-events-auto">
                <button type="submit" class="btn btn-primary btn-block btn-lg rounded-xl">{{ $submitLabel }}</button>
            </div>
        </div>
    </form>
</div>
