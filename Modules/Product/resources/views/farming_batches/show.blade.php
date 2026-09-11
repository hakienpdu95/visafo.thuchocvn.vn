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
    <a href="{{ route('backend.farming-batches.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
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
            <li>{{ $pesticideNames[$log->details['agri_pesticide_id'] ?? ''] ?? 'Thuốc BVTV' }} — phun {{ $log->activity_date->format('d/m/Y H:i') }}, an toàn sau {{ $log->safe_harvest_date?->format('d/m/Y') }}</li>
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
                <h2 class="text-base font-semibold mb-4">Thông tin chung</h2>
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
            <h2 class="text-base font-semibold mb-4">Nhật ký canh tác (Timeline)</h2>

            @if($farmingBatch->logs->isEmpty())
            <p class="text-sm text-base-content/50">Chưa có nhật ký nào được ghi nhận từ App Nông hộ.</p>
            @else
            <ol class="relative border-l-2 border-base-200 ml-2 space-y-6">
                @foreach($farmingBatch->logs as $log)
                @php
                    $isPesticide = $log->activity_type === 'pesticide';
                    $inQuarantine = $isPesticide && $log->safe_harvest_date && $log->safe_harvest_date->isFuture();
                    [$dotColor, $typeLabel] = match($log->activity_type) {
                        'cultivation' => ['bg-neutral', 'Canh tác'],
                        'water'       => ['bg-info', 'Tưới nước'],
                        'fertilizer'  => ['bg-success', 'Bón phân'],
                        'pesticide'   => [$inQuarantine ? 'bg-error' : 'bg-warning', 'Phun thuốc BVTV'],
                        'harvest'     => ['bg-primary', 'Thu hoạch'],
                        default       => ['bg-base-300', $log->activity_type],
                    };
                @endphp
                <li class="ml-5">
                    <span class="absolute -left-[9px] w-4 h-4 rounded-full {{ $dotColor }} border-2 border-base-100"></span>
                    <div class="p-3 rounded-lg border border-base-200 {{ $isPesticide && $inQuarantine ? 'bg-error/5 border-error/20' : ($isPesticide ? 'bg-warning/5 border-warning/20' : '') }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold">{{ $typeLabel }}</span>
                            <span class="text-xs text-base-content/40">{{ $log->activity_date->format('d/m/Y H:i') }}</span>
                        </div>

                        @if($log->activity_type === 'fertilizer')
                        <p class="text-xs text-base-content/70 mt-1">
                            {{ $fertilizerNames[$log->details['agri_fertilizer_id'] ?? ''] ?? 'Phân bón' }}
                            @if(!empty($log->details['quantity'])) — {{ $log->details['quantity'] }} {{ $log->details['unit'] ?? '' }} @endif
                        </p>
                        @elseif($log->activity_type === 'pesticide')
                        <p class="text-xs text-base-content/70 mt-1">
                            {{ $pesticideNames[$log->details['agri_pesticide_id'] ?? ''] ?? 'Thuốc BVTV' }}
                            @if(!empty($log->details['quantity'])) — {{ $log->details['quantity'] }} {{ $log->details['unit'] ?? '' }} @endif
                        </p>
                        @if($log->safe_harvest_date)
                        <p class="text-xs {{ $inQuarantine ? 'text-error' : 'text-success' }} mt-1 font-medium">
                            Ngày an toàn thu hoạch: {{ $log->safe_harvest_date->format('d/m/Y') }}
                            @if($inQuarantine) — còn cách ly @else — đã an toàn @endif
                        </p>
                        @endif
                        @elseif($log->activity_type === 'harvest')
                        <p class="text-xs text-base-content/70 mt-1">
                            @if(!empty($log->details['quantity'])) Sản lượng: {{ $log->details['quantity'] }} {{ $log->details['unit'] ?? '' }} @endif
                        </p>
                        @endif

                        @if($log->image_path)
                        <a href="{{ Illuminate\Support\Facades\Storage::url($log->image_path) }}" target="_blank" class="link link-primary text-xs mt-1 inline-block">Xem ảnh minh chứng</a>
                        @endif

                        @if($log->notes)
                        <p class="text-xs text-base-content/50 mt-1">{{ $log->notes }}</p>
                        @endif
                    </div>
                </li>
                @endforeach
            </ol>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/modules/toastify.js'], 'build/backend')
@endpush
