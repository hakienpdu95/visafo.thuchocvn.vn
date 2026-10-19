@extends('layouts.backend')
@section('title', $farmingBatch->batch_code)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2 font-mono">
            {{ $farmingBatch->batch_code }}
            @php
                $statusBadge = match($farmingBatch->status) {
                    'active' => 'badge-info', 'harvested' => 'badge-success', 'cancelled' => 'badge-ghost', default => 'badge-neutral',
                };
                $statusLabel = match($farmingBatch->status) {
                    'active' => 'Đang canh tác', 'harvested' => 'Đã thu hoạch', 'cancelled' => 'Đã hủy', default => $farmingBatch->status,
                };
            @endphp
            <span class="badge {{ $statusBadge }} badge-sm font-sans">{{ $statusLabel }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $farmingBatch->vendor?->name }} — {{ $farmingBatch->farmingSource?->name }}</p>
    </div>
    <div class="flex items-center gap-2">
        @can('compliance.manage')
        @if($farmingBatch->status === 'active')
        <a href="{{ route('farmer.batches.log.create', $farmingBatch) }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Ghi Nhật Ký
        </a>
        @endif
        @if($farmingBatch->status !== 'cancelled')
        <button type="button" class="btn btn-outline btn-sm gap-1.5" onclick="document.getElementById('batchLogImportModal').showModal()">
            📥 Import từ Excel
        </button>
        @endif
        @endcan
        <a href="{{ route('backend.farming-batches.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error py-2.5 px-4 mb-5 text-sm">{{ session('error') }}</div>
@endif
@include('product::farming_batches.partials.import-results')

@if($farmingBatch->status === 'active')
@can('approveHarvest', $farmingBatch)
<div class="card {{ $isReadyForHarvest ? 'bg-success/5 border-success/30' : 'bg-error/5 border-error/30' }} border shadow-sm mb-6">
    <div class="card-body">
        <h2 class="text-base font-semibold mb-1 flex items-center gap-2">
            <svg class="w-5 h-5 {{ $isReadyForHarvest ? 'text-success' : 'text-error' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Kiểm tra Readiness trước thu hoạch (BM-NH-04)
        </h2>

        @if($isReadyForHarvest)
        <p class="text-sm text-success mb-4">Không còn vật tư nào đang trong thời gian cách ly — lô đủ điều kiện thu hoạch.</p>
        @else
        <div class="alert alert-error py-2.5 px-4 mb-3 text-sm font-semibold">
            Lô đang trong thời gian cách ly thuốc (Còn {{ $quarantineDaysLeft }} ngày). Không được phép thu hoạch.
        </div>
        <p class="text-sm text-error mb-1">Các lần sử dụng vật tư chưa hết hạn cách ly:</p>
        <ul class="text-sm text-error/90 list-disc list-inside mb-4 space-y-0.5">
            @foreach($pendingQuarantineLogs as $log)
            <li>{{ $log->materialText() ?? $log->typeLabel() }} — {{ mb_strtolower($log->typeLabel()) }} {{ $log->activity_date->format('d/m/Y H:i') }}, cách ly {{ $log->isolation_days ?? $log->activity_date->startOfDay()->diffInDays($log->safe_harvest_date) }} ngày, an toàn từ {{ $log->safe_harvest_date?->format('d/m/Y') }}</li>
            @endforeach
        </ul>
        @endif

        <form method="POST" action="{{ route('backend.farming-batches.approve-harvest', $farmingBatch) }}"
              onsubmit="return confirm('Xác nhận phê duyệt cho phép thu hoạch lô này?');">
            @csrf
            <button type="submit" class="btn btn-sm {{ $isReadyForHarvest ? 'btn-success' : 'btn-disabled' }}" @disabled(!$isReadyForHarvest)>
                Phê duyệt cho phép thu hoạch
            </button>
        </form>
    </div>
</div>
@elseif($farmingBatch->pre_harvest_status === 'passed')
<div class="alert alert-success py-2.5 px-4 mb-6 text-sm">
    Đã phê duyệt cho phép thu hoạch bởi {{ $farmingBatch->preHarvestCheckedBy?->name ?? '—' }}
    lúc {{ $farmingBatch->pre_harvest_checked_at?->format('d/m/Y H:i') }}.
</div>
@endif
@endif

<div class="grid grid-cols-1 lg:grid-cols-[360px_1fr] gap-6 items-start">

    <div class="space-y-6">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Thông tin chung</h2>
                <dl class="text-sm space-y-3">
                    <div><dt class="text-xs text-base-content/40">Mã lô (LOT_ID)</dt><dd class="font-mono font-medium">{{ $farmingBatch->batch_code }}</dd></div>
                    <div><dt class="text-xs text-base-content/40">Nông hộ</dt><dd class="font-medium">{{ $farmingBatch->vendor?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-base-content/40">Vùng trồng</dt><dd class="font-medium">{{ $farmingBatch->farmingSource?->name ?? '—' }}</dd></div>
                    @if($farmingBatch->farmingSource?->address)
                    <div><dt class="text-xs text-base-content/40">Địa chỉ vùng trồng</dt><dd class="font-medium">{{ $farmingBatch->farmingSource->address }}</dd></div>
                    @endif
                    @if($farmingBatch->farmingSource?->areaText())
                    <div><dt class="text-xs text-base-content/40">Diện tích vùng trồng</dt><dd class="font-medium">{{ $farmingBatch->farmingSource->areaText() }}</dd></div>
                    @endif
                    <div><dt class="text-xs text-base-content/40">Giống cây trồng</dt><dd class="font-medium">{{ $farmingBatch->agriSeed?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-base-content/40">Mặt hàng thương mại</dt><dd class="font-medium">{{ $farmingBatch->partnerProduct?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-base-content/40">Ngày gieo</dt><dd class="font-medium">{{ $farmingBatch->sowing_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-base-content/40">Dự kiến thu hoạch</dt><dd class="font-medium">{{ $farmingBatch->expected_harvest_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-base-content/40">Thu hoạch thực tế</dt><dd class="font-medium">{{ $farmingBatch->actual_harvest_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    @if($farmingBatch->notes)
                    <div><dt class="text-xs text-base-content/40">Ghi chú</dt><dd class="font-medium">{{ $farmingBatch->notes }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-5">Nhật ký canh tác (Timeline)</h2>

            @php
                $logRows = $farmingBatch->logs->map(function ($log) {
                    $inQuarantine = $log->safe_harvest_date && $log->safe_harvest_date->isFuture();
                    $typeLabel = $log->typeLabel();
                    $typeBadge = $log->vendorFarmingStep
                        ? 'badge-info'
                        : (\Modules\Product\Models\FarmingLog::ACTIVITY_TYPES[$log->activity_type]['badge'] ?? 'badge-ghost');
                    if ($inQuarantine) {
                        $typeBadge = 'badge-error';
                    }

                    $isolationDays = $log->isolation_days ?? ($log->safe_harvest_date ? $log->activity_date->copy()->startOfDay()->diffInDays($log->safe_harvest_date) : null);
                    $usesMaterial = in_array($log->activity_type, \Modules\Product\Models\FarmingLog::MATERIAL_TYPES, true);
                    $isHarvest = $log->activity_type === 'harvest';

                    $quarantineText = null;
                    if ($log->safe_harvest_date) {
                        $quarantineText = 'Ngày an toàn thu hoạch: ' . $log->safe_harvest_date->format('d/m/Y')
                            . ($inQuarantine ? ' — còn cách ly ' . today()->diffInDays($log->safe_harvest_date) . ' ngày' : ' — đã an toàn');
                    }

                    return [
                        'id'               => $log->id,
                        'activity_date'    => $log->activity_date->format('d/m/Y H:i'),
                        'activity_date_ts' => $log->activity_date->timestamp,
                        'type_label'       => $typeLabel,
                        'type_badge'       => $typeBadge,
                        'notes'            => $log->notes,
                        'material'         => $usesMaterial ? $log->materialText() : null,
                        'dosage'           => $usesMaterial ? $log->dosageText() : null,
                        'isolation_days'   => $usesMaterial && $isolationDays ? (int) $isolationDays : null,
                        'target'           => $usesMaterial ? $log->method_or_target : null,
                        'harvest_yield'    => $isHarvest ? $log->harvestYieldText() : null,
                        'buyer_name'       => $isHarvest ? $log->buyer_name : null,
                        'is_harvest'       => $isHarvest,
                        'quarantine_text'  => $quarantineText,
                        'in_quarantine'    => $inQuarantine,
                        'operator'         => $log->operatorText(),
                        'image_url'        => $log->image_path ? \Illuminate\Support\Facades\Storage::url($log->image_path) : null,
                    ];
                })->values();
            @endphp

            <div class="tabulator-daisy">
                <div id="farming-log-table"
                     data-can-manage="{{ auth()->user()->can('compliance.manage') ? '1' : '0' }}"
                     data-edit-url-template="{{ route('farmer.logs.edit', ['farming_log' => '__ID__']) }}"
                     data-delete-url-template="{{ route('farmer.logs.destroy', ['farming_log' => '__ID__']) }}"
                     data-rows="{{ json_encode($logRows, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
            </div>
        </div>
    </div>

</div>

@can('compliance.manage')
<dialog id="batchLogImportModal" class="modal" x-data="farmingBatchLogImport({{ Js::from([
    'previewUrl' => route('backend.farming-batches.log-import.preview'),
    'discardUrl' => route('backend.farming-batches.log-import.discard', ['token' => '__TOKEN__']),
    'batchId'    => $farmingBatch->id,
]) }})" @close="discard()">
    <div class="modal-box max-w-xl">
        <h3 class="font-semibold text-base mb-1">Import nhật ký vào lô {{ $farmingBatch->batch_code }}</h3>
        <p class="text-xs text-base-content/50 mb-4">
            Dữ liệu chỉ được ghi vào lô này{{ $farmingBatch->sowing_date ? ' (gieo ' . $farmingBatch->sowing_date->format('d/m/Y') . ')' : '' }}.
            Nếu file có nhiều sheet, bạn chọn đúng sheet của lô.
            <a href="{{ route('backend.farming-batches.log-import-template') }}" class="link link-primary">Tải file Excel mẫu</a>
        </p>

        <div x-show="error" class="alert alert-error py-2 px-3 mb-3 text-sm" x-text="error"></div>

        <template x-if="step === 'upload'">
            <form @submit.prevent="preview($event)" class="space-y-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">File nhật ký (.xlsx) <span class="text-error">*</span></span></label>
                    <input type="file" name="file" accept=".xlsx" class="file-input file-input-bordered file-input-sm w-full" required>
                </div>
                <div class="modal-action mt-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">
                        <span x-show="loading" class="loading loading-spinner loading-xs"></span>
                        Đọc file
                    </button>
                </div>
            </form>
        </template>

        <template x-if="step === 'pick'">
            <form method="POST" action="{{ route('backend.farming-batches.log-import.store') }}" class="space-y-4" @submit="submitted = true">
                @csrf
                <input type="hidden" name="token" :value="token">
                <input type="hidden" name="return_batch_id" value="{{ $farmingBatch->id }}">
                <input type="hidden" name="mappings[0][sheet]" :value="selectedSheet">
                <input type="hidden" name="mappings[0][batch_id]" value="{{ $farmingBatch->id }}">

                <p class="text-sm font-medium" x-text="sheets.length > 1 ? 'Chọn sheet cần import (' + sheets.length + ' sheet trong file):' : 'Sheet trong file:'"></p>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    <template x-for="sheet in sheets" :key="sheet.name">
                        <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                               :class="selectedSheet === sheet.name ? 'border-primary bg-primary/5' : 'border-base-200 hover:bg-base-200/50'">
                            <input type="radio" class="radio radio-primary radio-sm mt-0.5" :value="sheet.name" x-model="selectedSheet">
                            <div class="min-w-0 text-sm">
                                <p class="font-medium">📄 <span x-text="sheet.name"></span></p>
                                <p class="text-xs text-base-content/60 mt-0.5">
                                    Gieo: <span x-text="sheet.sowing_date_text || 'không rõ ngày'"></span>
                                    <template x-if="sheet.owner"><span> · <span x-text="sheet.owner"></span></span></template>
                                    · <span x-text="sheet.rows"></span> dòng nhật ký
                                    <template x-if="sheet.errors > 0"><span class="text-error"> · <span x-text="sheet.errors"></span> dòng lỗi</span></template>
                                </p>
                                <template x-if="matchesBatch(sheet)">
                                    <p class="text-xs mt-0.5" :class="sheet.suggestion.level === 'owner_crop' ? 'text-warning' : 'text-success'"
                                       x-text="(sheet.suggestion.level === 'owner_crop' ? '⚠ ' : '✓ ') + sheet.suggestion.note"></p>
                                </template>
                                <template x-if="sheet.suggestion && !matchesBatch(sheet)">
                                    <p class="text-xs mt-0.5 text-warning">⚠ Sheet này có vẻ thuộc lô khác — kiểm tra kỹ trước khi import.</p>
                                </template>
                            </div>
                        </label>
                    </template>
                </div>

                <div class="modal-action mt-2">
                    <button type="button" class="btn btn-ghost btn-sm" @click="discard()">Chọn file khác</button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="!selectedSheet">Import vào {{ $farmingBatch->batch_code }}</button>
                </div>
            </form>
        </template>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan

<dialog id="logImageLightbox" class="modal">
    <div class="modal-box max-w-3xl p-2 bg-transparent shadow-none">
        <img id="logImageLightboxImg" src="" class="w-full h-auto rounded-lg">
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tabulator.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
