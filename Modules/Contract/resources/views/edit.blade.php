@extends('layouts.backend')
@section('title', 'Chỉnh sửa hợp đồng')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa hợp đồng</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $contract->contract_number }} — {{ $contract->name }}</p>
    </div>
    <a href="{{ route('backend.contracts.show', $contract) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
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

@php
    $mediaUrlService = app(\App\Services\Media\MediaUrlService::class);
    $existingMedia = $contract->getMedia('attachments_private')->map(fn ($m) => [
        'id'         => $m->id,
        'name'       => $m->file_name,
        'size'       => $m->size,
        'is_image'   => str_starts_with($m->mime_type, 'image/'),
        'url'        => $mediaUrlService->url($m),
        'delete_url' => route('backend.contracts.media.destroy', [$contract, $m->id]),
    ])->values();
@endphp

<form method="POST" action="{{ route('backend.contracts.update', $contract) }}" enctype="multipart/form-data" novalidate data-contract-form
      x-data="{
          autoRenew: {{ old('is_auto_renew', $contract->is_auto_renew) ? 'true' : 'false' }},
          contractType: '{{ old('type', $contract->type->value) }}',
          files: [],
          existingMedia: {{ Js::from($existingMedia) }},
          removingMediaId: null,
          onFilesChange(event) {
              this.files = Array.from(event.target.files ?? []);
          },
          removeFile(index) {
              this.files.splice(index, 1);
              const dt = new DataTransfer();
              this.files.forEach((file) => dt.items.add(file));
              this.$refs.contractFilesInput.files = dt.files;
          },
          isImageFile(file) {
              return file.type.startsWith('image/');
          },
          formatFileSize(bytes) {
              if (bytes < 1024) return bytes + ' B';
              if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
              return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
          },
          async removeExistingMedia(media) {
              if (this.removingMediaId || !confirm('Xóa file ' + media.name + '?')) return;

              this.removingMediaId = media.id;
              const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';

              try {
                  const res = await fetch(media.delete_url, {
                      method: 'POST',
                      headers: {
                          'X-CSRF-TOKEN':     csrf,
                          'X-Requested-With': 'XMLHttpRequest',
                          'Accept':           'application/json',
                          'Content-Type':     'application/x-www-form-urlencoded',
                      },
                      body: '_method=DELETE',
                  });

                  if (res.ok) {
                      this.existingMedia = this.existingMedia.filter((m) => m.id !== media.id);
                  } else {
                      const data = await res.json().catch(() => ({}));
                      alert(data.message || 'Xóa file thất bại. Vui lòng thử lại.');
                  }
              } catch (e) {
                  alert('Lỗi kết nối. Vui lòng thử lại.');
              } finally {
                  this.removingMediaId = null;
              }
          },
      }">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        <div class="space-y-6">

            {{-- ── Khối 1: Đối tác & thông tin hợp đồng ────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h18v4H3V3zm2 4h14v14H5V7zm3 4h8m-8 4h5"/>
                        </svg>
                        Thông tin hợp đồng
                    </h2>

                    <div class="space-y-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại giao dịch</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không thể thay đổi sau khi tạo</span>
                            </label>
                            <div class="flex flex-wrap gap-6">
                                <label class="label justify-start gap-2 py-0 cursor-not-allowed opacity-60">
                                    <input type="radio" value="input" disabled
                                           @checked($contract->type->value === 'input')
                                           class="radio radio-sm radio-primary">
                                    <span class="label-text font-medium">Đầu vào (Nhà cung cấp)</span>
                                </label>
                                <label class="label justify-start gap-2 py-0 cursor-not-allowed opacity-60">
                                    <input type="radio" value="output" disabled
                                           @checked($contract->type->value === 'output')
                                           class="radio radio-sm radio-primary">
                                    <span class="label-text font-medium">Đầu ra (Khách hàng)</span>
                                </label>
                            </div>
                            <input type="hidden" name="type" value="{{ $contract->type->value }}">
                        </div>

                        <div x-show="contractType === 'input'" x-cloak class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nhà cung cấp <span class="text-error">*</span></span>
                            </label>
                            <input type="hidden" name="vendor_id" value="{{ $contract->vendor_id }}">
                            <input type="text" value="{{ $contract->vendor?->name ?? '—' }}" readonly disabled
                                   class="input input-bordered input-sm w-full bg-base-200/60">
                            <p class="mt-1 text-xs text-base-content/40">Không thể thay đổi nhà cung cấp sau khi tạo hợp đồng.</p>
                            @error('vendor_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div x-show="contractType === 'output'" x-cloak class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Khách hàng <span class="text-error">*</span></span>
                            </label>
                            <input type="hidden" name="customer_id" value="{{ $contract->customer_id }}">
                            <input type="text" value="{{ $contract->customer?->name ?? '—' }}" readonly disabled
                                   class="input input-bordered input-sm w-full bg-base-200/60">
                            <p class="mt-1 text-xs text-base-content/40">Không thể thay đổi khách hàng sau khi tạo hợp đồng.</p>
                            @error('customer_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Loại hợp đồng <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-contract_type_id" name="contract_type_id"
                                        class="select select-bordered select-sm w-full ts-init @error('contract_type_id') select-error @enderror"
                                        data-ts-placeholder="— Chọn loại hợp đồng —"
                                        data-req="Vui lòng chọn loại hợp đồng">
                                    <option value="">— Chọn loại hợp đồng —</option>
                                    @foreach($contractTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('contract_type_id', $contract->contract_type_id) === $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('contract_type_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Số hợp đồng</span>
                                    <span class="label-text-alt text-xs text-base-content/40">Hệ thống tự sinh, không thể sửa</span>
                                </label>
                                <input type="text" value="{{ $contract->contract_number }}"
                                       class="input input-bordered input-sm w-full font-mono field-readonly"
                                       readonly tabindex="-1">
                            </div>

                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên hợp đồng <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $contract->name) }}"
                                   data-req="Vui lòng nhập tên hợp đồng"
                                   data-val-maxlength="255"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Hợp đồng cung cấp rau củ quý IV/2026" autofocus>
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giá trị hợp đồng</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn — bỏ trống với hợp đồng nguyên tắc</span>
                            </label>
                            <input type="text" inputmode="decimal" name="total_value" value="{{ old('total_value', $contract->total_value) }}"
                                   class="input input-bordered input-sm w-full font-mono @error('total_value') input-error @enderror"
                                   placeholder="VD: 500000000">
                            @error('total_value')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Ngày bắt đầu <span class="text-error">*</span></span>
                                </label>
                                <input type="text" name="start_date" id="fp-start-date"
                                       value="{{ old('start_date', $contract->start_date?->format('d/m/Y')) }}"
                                       data-req="Vui lòng chọn ngày bắt đầu"
                                       class="input input-bordered input-sm w-full fp-init @error('start_date') input-error @enderror"
                                       placeholder="DD/MM/YYYY">
                                @error('start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Ngày kết thúc</span>
                                    <span class="label-text-alt text-xs text-base-content/40">Bỏ trống nếu vô thời hạn</span>
                                </label>
                                <input type="text" name="end_date" id="fp-end-date"
                                       value="{{ old('end_date', $contract->end_date?->format('d/m/Y')) }}"
                                       class="input input-bordered input-sm w-full fp-init @error('end_date') input-error @enderror"
                                       placeholder="DD/MM/YYYY">
                                @error('end_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>

                    </div>

                </div>
            </div>

            {{-- ── Khối 2: Tự động gia hạn ──────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-1">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Gia hạn hợp đồng
                    </h2>
                    <p class="text-xs text-base-content/40 mb-4">Hệ thống tự động dời ngày kết thúc mỗi ngày khi hợp đồng đến/quá hạn</p>

                    <label class="label cursor-pointer justify-start gap-3 py-0 mb-3">
                        <input type="checkbox" name="is_auto_renew" value="1" class="checkbox checkbox-sm"
                               x-model="autoRenew" @checked(old('is_auto_renew', $contract->is_auto_renew))>
                        <span class="label-text font-medium">Tự động gia hạn</span>
                    </label>

                    <div x-show="autoRenew" x-transition x-cloak class="form-control max-w-xs">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Chu kỳ gia hạn (số tháng) <span class="text-error">*</span></span>
                        </label>
                        <input type="number" name="renewal_period_months" min="1" max="120"
                               value="{{ old('renewal_period_months', $contract->renewal_period_months) }}"
                               :required="autoRenew"
                               class="input input-bordered input-sm w-full @error('renewal_period_months') input-error @enderror"
                               placeholder="VD: 12">
                        @error('renewal_period_months')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>

            {{-- ── Khối 3: Tài liệu đính kèm ─────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-1">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Tài liệu đính kèm
                    </h2>
                    <p class="text-xs text-base-content/40 mb-4">Bản scan hợp đồng, phụ lục... — có thể chọn nhiều file cùng lúc</p>

                    <div class="form-control" x-show="existingMedia.length > 0" x-cloak>
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Các tệp đã tải lên</span></label>
                        <template x-for="media in existingMedia" :key="media.id">
                            <div class="flex items-center justify-between p-2.5 mb-2 bg-gray-50 border border-gray-200 rounded-lg">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg x-show="media.is_image" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <svg x-show="!media.is_image" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="truncate text-sm text-gray-700" x-text="media.name"></span>
                                    <span class="shrink-0 text-xs text-gray-400" x-text="formatFileSize(media.size)"></span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <a :href="media.url" target="_blank" class="btn btn-ghost btn-xs">Xem</a>
                                    <button type="button" class="btn btn-ghost btn-xs text-error" :disabled="removingMediaId === media.id"
                                            @click="removeExistingMedia(media)" title="Xóa file này">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1">
                            <span class="label-text-alt text-xs text-base-content/40">PDF, JPG, PNG, DOC, DOCX, XLS, XLSX — tối đa 100MB/file</span>
                        </label>
                        <input type="file" name="files[]" x-ref="contractFilesInput" multiple
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                               class="file-input file-input-bordered file-input-sm w-full @error('files') input-error @enderror"
                               @change="onFilesChange($event)">
                        @error('files')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @error('files.*')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror

                        <ul class="mt-2 space-y-1" x-show="files.length > 0" x-cloak>
                            <template x-for="(file, index) in files" :key="index">
                                <li class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg x-show="isImageFile(file)" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <svg x-show="!isImageFile(file)" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="truncate text-xs text-gray-700" x-text="file.name"></span>
                                        <span class="shrink-0 text-xs text-base-content/40" x-text="formatFileSize(file.size)"></span>
                                    </div>
                                    <button type="button" class="btn btn-ghost btn-xs btn-circle shrink-0" @click="removeFile(index)" title="Bỏ chọn file này">✕</button>
                                </li>
                            </template>
                        </ul>
                    </div>

                </div>
            </div>

        </div>

        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach(\Modules\Contract\Enums\ContractStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $contract->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.contracts.show', $contract) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu thay đổi
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>
        </div>

    </div>
</form>
@endsection

@push('styles')
    @vite(['Modules/Contract/resources/assets/sass/contract.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Contract/resources/assets/js/contract.js',
    ], 'build/backend')
@endpush
