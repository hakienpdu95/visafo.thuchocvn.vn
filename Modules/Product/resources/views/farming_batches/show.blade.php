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
        <p class="text-sm text-success mb-4">Không còn thuốc BVTV nào đang trong thời gian cách ly — lô đủ điều kiện thu hoạch.</p>
        @else
        <p class="text-sm text-error mb-2">Chưa đủ thời gian cách ly — <strong>cấm thu hoạch</strong>. Các lần phun thuốc sau chưa hết hạn cách ly:</p>
        <ul class="text-sm text-error/90 list-disc list-inside mb-4 space-y-0.5">
            @foreach($pendingQuarantineLogs as $log)
            <li>{{ $log->agriPesticide?->trade_name ?? 'Thuốc BVTV' }} — phun {{ $log->activity_date->format('d/m/Y H:i') }}, an toàn sau {{ $log->safe_harvest_date?->format('d/m/Y') }}</li>
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
                    $isPesticide = $log->activity_type === 'pesticide';
                    $inQuarantine = $isPesticide && $log->safe_harvest_date && $log->safe_harvest_date->isFuture();

                    [$typeBadge, $typeLabel] = match($log->activity_type) {
                        'cultivation' => ['badge-neutral', 'Canh tác'],
                        'water'       => ['badge-info', 'Tưới nước'],
                        'fertilizer'  => ['badge-success', 'Bón phân'],
                        'pesticide'   => [$inQuarantine ? 'badge-error' : 'badge-warning', 'Phun thuốc BVTV'],
                        'harvest'     => ['badge-primary', 'Thu hoạch'],
                        'other'       => ['badge-ghost', 'Khác'],
                        default       => ['badge-ghost', $log->activity_type],
                    };
                    if ($log->vendorFarmingStep) {
                        $typeBadge = 'badge-info';
                        $typeLabel = $log->vendorFarmingStep->step_name;
                    }

                    $detail = match($log->activity_type) {
                        'fertilizer' => trim(
                            ($log->agriFertilizer?->name ?? 'Phân bón')
                            . ($log->quantity ? ' — ' . $log->quantity . ' ' . $log->unit : '')
                            . ($log->method_or_target ? ' (' . $log->method_or_target . ')' : '')
                        ),
                        'pesticide' => trim(
                            ($log->agriPesticide?->trade_name ?? 'Thuốc BVTV')
                            . ($log->quantity ? ' — ' . $log->quantity . ' ' . $log->unit : '')
                            . ($log->method_or_target ? ' — ' . $log->method_or_target : '')
                        ),
                        'harvest' => $log->quantity ? 'Sản lượng: ' . $log->quantity . ' ' . $log->unit : '',
                        default => '',
                    };

                    $quarantineText = null;
                    if ($isPesticide && $log->safe_harvest_date) {
                        $quarantineText = 'Ngày an toàn thu hoạch: ' . $log->safe_harvest_date->format('d/m/Y')
                            . ($inQuarantine ? ' — còn cách ly' : ' — đã an toàn');
                    }

                    return [
                        'id'               => $log->id,
                        'activity_date'    => $log->activity_date->format('d/m/Y H:i'),
                        'activity_date_ts' => $log->activity_date->timestamp,
                        'type_label'       => $typeLabel,
                        'type_badge'       => $typeBadge,
                        'detail'           => $detail,
                        'quarantine_text'  => $quarantineText,
                        'in_quarantine'    => $inQuarantine,
                        'image_url'        => $log->image_path ? \Illuminate\Support\Facades\Storage::url($log->image_path) : null,
                        'notes'            => $log->notes,
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
