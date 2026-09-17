@extends('layouts.backend')
@section('title', 'Tạo gói chào hàng')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo gói chào hàng</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Chọn khách hàng, rà soát hồ sơ và chốt gói chào hàng</p>
    </div>
    <a href="{{ route('backend.sales-packages.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div x-data="salesPackageWizard({{ Js::from([
    'checklistUrlTemplate' => route('backend.api.customers.package-checklist', ['customer' => '__ID__']),
    'customerId' => old('customer_id', ''),
    'errors'     => $errors->keys(),
]) }})">

    {{-- Step indicator --}}
    <div class="flex items-center gap-0 mb-8">
        <template x-for="(label, idx) in steps" :key="idx">
            <div class="flex items-center flex-1 last:flex-none">
                <div class="flex flex-col items-center gap-1">
                    <div class="wizard-step-dot" :class="stepDotClass(idx)">
                        <template x-if="currentStep > idx + 1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        </template>
                        <template x-if="currentStep <= idx + 1">
                            <span x-text="idx + 1"></span>
                        </template>
                    </div>
                    <span class="text-xs whitespace-nowrap"
                          :class="currentStep === idx + 1 ? 'text-primary font-semibold' : 'text-base-content/40'"
                          x-text="label"></span>
                </div>
                <template x-if="idx < steps.length - 1">
                    <div class="wizard-step-line" :class="stepLineClass(idx)"></div>
                </template>
            </div>
        </template>
    </div>

    <form method="POST" action="{{ route('backend.sales-packages.store') }}" novalidate enctype="multipart/form-data"
          data-sales-package-form @submit="handleSubmit()">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    {{-- Step 1 — Chọn khách hàng --}}
                    <div x-show="currentStep === 1" data-tab-label="Chọn khách hàng" class="space-y-4">
                        <div class="form-control max-w-md">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Khách hàng / cơ hội <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-customer_id" name="customer_id"
                                    class="select select-bordered select-sm w-full @error('customer_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn khách hàng —"
                                    data-req="Vui lòng chọn khách hàng">
                                <option value="">— Chọn khách hàng —</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->customer_code }})
                                </option>
                                @endforeach
                            </select>
                            @error('customer_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="button" @click="nextStep()" class="btn btn-primary btn-sm gap-1.5">
                                Tiếp theo: Chọn thành phần hồ sơ
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 2 — Chọn thành phần hồ sơ --}}
                    <div x-show="currentStep === 2" x-cloak data-tab-label="Chọn thành phần hồ sơ" class="space-y-5">

                        <div x-show="loading" class="space-y-3 py-2">
                            <div class="skeleton h-4 w-full rounded"></div>
                            <div class="skeleton h-4 w-3/4 rounded"></div>
                        </div>

                        <template x-for="(group, groupName) in groupedItems" :key="groupName">
                            <div>
                                <div class="font-semibold text-sm text-base-content/70 mb-2" x-text="groupName"></div>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <template x-for="item in group" :key="item.label">
                                        <div class="bg-base-100 rounded-xl border border-base-200 p-4 flex items-center justify-between gap-3">
                                            <label class="flex items-start gap-3 min-w-0 cursor-pointer select-none"
                                                   :class="!item.compliance_document_id ? 'opacity-50' : ''">
                                                <input type="checkbox" name="document_ids[]"
                                                       class="checkbox checkbox-primary mt-0.5 shrink-0"
                                                       :value="item.compliance_document_id"
                                                       x-model="selectedIds"
                                                       :disabled="!item.compliance_document_id"
                                                       @click="if (item.required && item.satisfied) { $event.preventDefault(); }">
                                                <div class="min-w-0">
                                                    <span class="text-sm font-semibold block" x-text="item.label"></span>
                                                    <span class="text-xs text-base-content/50 flex items-center gap-1 mt-0.5">
                                                        <span x-text="item.compliance_document_id ? '1 tệp đính kèm' : 'Chưa có tệp đính kèm'"></span>
                                                        <span>·</span>
                                                        <span x-text="item.required ? 'Bắt buộc' : 'Tùy chọn'"></span>
                                                        <template x-if="item.required && item.satisfied">
                                                            <svg class="w-3 h-3 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                                        </template>
                                                    </span>
                                                    <p class="text-xs text-base-content/40 mt-0.5" x-text="item.note"></p>
                                                </div>
                                            </label>

                                            <span class="badge badge-sm badge-soft shrink-0"
                                                  :class="!item.satisfied ? 'badge-error' : (item.is_expiring_soon ? 'badge-warning' : 'badge-success')"
                                                  x-text="!item.satisfied ? 'Thiếu hồ sơ' : (item.is_expiring_soon ? 'Cảnh báo' : 'Hợp lệ')"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="rounded-xl border border-dashed border-base-300 p-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <h3 class="text-sm font-semibold">Tài liệu bổ sung ngoài hệ thống</h3>
                                    <p class="text-xs text-base-content/50 mt-0.5">Đính kèm tài liệu không có trong checklist chuẩn (VD: Bảng giá, Catalogue...)</p>
                                </div>
                                <button type="button" @click="openCustomDocModal()" class="btn btn-ghost btn-sm gap-1.5 shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tải lên tài liệu khác
                                </button>
                            </div>

                            <template x-if="customDocuments.length === 0">
                                <p class="text-xs text-base-content/40">Chưa có tài liệu bổ sung nào.</p>
                            </template>

                            <div class="space-y-2">
                                <template x-for="(doc, index) in customDocuments" :key="doc.id">
                                    <div class="flex items-center justify-between gap-2 bg-base-200/50 rounded-lg px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium truncate" x-text="doc.name"></p>
                                            <p class="text-xs text-base-content/40 truncate" x-text="doc.fileName"></p>
                                        </div>
                                        <button type="button" class="btn btn-ghost btn-xs text-error shrink-0" @click="removeCustomDocument(index)">Xóa</button>
                                        <input type="hidden" :name="'custom_documents[' + index + '][name]'" :value="doc.name">
                                        <input type="file" class="hidden" :data-custom-doc-id="doc.id" :name="'custom_documents[' + index + '][file]'">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <button type="button" @click="prevStep()" class="btn btn-ghost btn-sm gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                                Khách hàng
                            </button>
                            <button type="button" @click="nextStep()" class="btn btn-primary btn-sm gap-1.5">
                                Tiếp theo: Thông tin gói
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 3 — Thông tin gói --}}
                    <div x-show="currentStep === 3" x-cloak data-tab-label="Thông tin gói" class="space-y-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên gói <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" x-model="name"
                                   data-req="Vui lòng đặt tên gói chào hàng"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Gói chào suất ăn bán trú 2026">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control max-w-xs">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Hạn nộp dự kiến</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Tự động điền theo KH</span>
                            </label>
                            <input type="text" name="expected_deadline" id="fp-expected-deadline"
                                   class="input input-bordered input-sm w-full fp-init @error('expected_deadline') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('expected_deadline')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ghi chú nội bộ</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span>
                            </label>
                            <textarea name="notes" x-model="notes" rows="3"
                                      class="textarea textarea-bordered textarea-sm w-full"
                                      placeholder="Ghi chú nội bộ..."></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <button type="button" @click="prevStep()" class="btn btn-ghost btn-sm gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                                Thành phần hồ sơ
                            </button>
                            <div class="flex gap-2">
                                <button type="button" @click="$refs.previewModal.showModal()" class="btn btn-ghost btn-sm gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Xem trước mục lục
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="submitting" :class="{ 'btn-disabled': submitting }">
                                    <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                                    <span x-text="submitting ? 'Đang xử lý...' : 'Tạo gói chào hàng'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Sidebar — Tóm tắt gói (sticky, realtime) --}}
            <div class="xl:sticky xl:top-4 space-y-4">
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Tóm tắt gói</p>

                        <template x-if="!customer_id">
                            <p class="text-sm text-base-content/40">Chưa chọn khách hàng.</p>
                        </template>

                        <template x-if="customer_id">
                            <div class="space-y-3">
                                <div class="rounded-xl bg-green-800 text-white p-4 text-center">
                                    <div class="text-3xl font-bold" x-text="score"></div>
                                    <div class="text-xs text-white/70">/ 100 điểm sẵn sàng</div>
                                </div>

                                <div class="flex justify-between text-xs text-base-content/40 px-0.5">
                                    <span>Khách hàng</span>
                                    <span class="font-medium text-base-content/70 text-right" x-text="customerName"></span>
                                </div>
                                <div class="flex justify-between text-xs text-base-content/40 px-0.5">
                                    <span>Hạng mục đủ hồ sơ</span>
                                    <span class="font-medium text-base-content/70" x-text="satisfiedCount + '/' + total"></span>
                                </div>
                                <div class="flex justify-between text-xs text-base-content/40 px-0.5">
                                    <span>Tài liệu đóng gói</span>
                                    <span class="font-medium text-base-content/70" x-text="selectedIds.length + '/' + total"></span>
                                </div>
                                <div class="flex justify-between text-xs text-base-content/40 px-0.5">
                                    <span>Tài liệu bổ sung</span>
                                    <span class="font-medium text-base-content/70" x-text="customDocuments.length"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </form>

    <dialog x-ref="customDocModal" class="modal">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-lg">Tải lên tài liệu khác</h3>
            <p class="text-sm text-base-content/50 mb-4">Tài liệu bổ sung ngoài checklist chuẩn</p>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Tên tài liệu <span class="text-error">*</span></span>
                </label>
                <input type="text" x-model="customDocDraft.name"
                       class="input input-bordered input-sm w-full"
                       placeholder="VD: Bảng giá tham khảo 2026">
            </div>

            <div class="form-control mt-3">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">File đính kèm <span class="text-error">*</span></span>
                </label>
                <input type="file" x-ref="customDocFileInput"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                       class="file-input file-input-bordered file-input-sm w-full">
            </div>

            <div class="modal-action mt-4">
                <button type="button" class="btn btn-ghost btn-sm" @click="$refs.customDocModal.close()">Hủy</button>
                <button type="button" class="btn btn-primary btn-sm" @click="addCustomDocument()">Thêm</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog x-ref="previewModal" class="modal">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg">Mục lục xem trước</h3>
            <p class="text-sm text-base-content/50 mb-4">Danh sách tài liệu đang được chọn để đóng gói</p>

            <div class="space-y-1 max-h-96 overflow-y-auto">
                <template x-for="(item, idx) in previewItems" :key="idx">
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-base-200 last:border-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-xs font-mono text-base-content/40 w-6 shrink-0" x-text="String(idx + 1).padStart(2, '0')"></span>
                            <span class="text-sm font-medium truncate" x-text="item.label"></span>
                        </div>
                        <span class="badge badge-sm badge-soft shrink-0"
                              :class="item.is_expiring_soon ? 'badge-warning' : 'badge-success'"
                              x-text="item.is_expiring_soon ? 'Cần cập nhật' : 'Sẵn sàng'"></span>
                    </div>
                </template>
                <template x-if="previewItems.length === 0">
                    <p class="text-sm text-base-content/40 text-center py-6">Chưa có tài liệu nào được chọn.</p>
                </template>
            </div>

            <div class="modal-action mt-4">
                <button type="button" class="btn btn-ghost btn-sm" @click="$refs.previewModal.close()">Đóng</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>

@endsection

@push('styles')
    @vite(['Modules/SalesPackage/resources/assets/sass/salespackage.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/SalesPackage/resources/assets/js/salespackage.js',
    ], 'build/backend')
@endpush
