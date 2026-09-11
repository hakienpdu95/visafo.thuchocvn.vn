@extends('layouts.mobile')
@section('title', 'Nhật ký của tôi')
@section('subtitle', auth()->user()->vendor?->name ?? auth()->user()->name)

@section('content')

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error py-2.5 px-4 mb-4 text-sm">{{ session('error') }}</div>
@endif

<h1 class="text-lg font-bold text-base-content mb-1">Vụ/Lô đang canh tác</h1>
<p class="text-sm text-base-content/50 mb-4">Chọn một lô để ghi nhật ký hoạt động canh tác hôm nay.</p>

@if($batches->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-10">
        <p class="text-sm text-base-content/50">Bạn chưa được mở Vụ/Lô sản xuất nào.</p>
    </div>
</div>
@else
<div class="space-y-4">
    @foreach($batches as $batch)
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <p class="font-mono font-bold text-base">{{ $batch->batch_code }}</p>
            <dl class="text-sm mt-1 space-y-1">
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Nông hộ</dt>
                    <dd class="font-medium">{{ $batch->vendor?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Giống cây trồng</dt>
                    <dd class="font-medium">{{ $batch->agriSeed?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Vùng trồng</dt>
                    <dd class="font-medium">{{ $batch->farmingSource?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Ngày gieo</dt>
                    <dd class="font-medium">{{ $batch->sowing_date?->format('d/m/Y') ?? '—' }}</dd>
                </div>
            </dl>

            <a href="{{ route('farmer.batches.log.create', $batch) }}" class="btn btn-primary btn-block mt-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Ghi Nhật Ký
            </a>

            @if($batch->recentLogs->isNotEmpty())
            <div class="mt-3 pt-3 border-t border-base-200">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Nhật ký gần đây</p>
                <div class="space-y-1.5">
                    @foreach($batch->recentLogs as $log)
                    @php
                        $typeLabel = $log->vendorFarmingStep?->step_name ?? match($log->activity_type) {
                            'water' => 'Tưới nước', 'fertilizer' => 'Bón phân', 'pesticide' => 'Phun thuốc BVTV',
                            'harvest' => 'Thu hoạch', 'other' => 'Khác', 'cultivation' => 'Canh tác', default => $log->activity_type,
                        };
                        $canMutate = auth()->user()->can('compliance.manage') || $log->created_at->isAfter(now()->subHours(24));
                    @endphp
                    <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-base-200/50">
                        <div class="min-w-0">
                            <p class="text-xs font-medium">{{ $typeLabel }}</p>
                            <p class="text-xs text-base-content/50">{{ $log->activity_date->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($canMutate)
                        <div class="flex items-center gap-1 shrink-0">
                            <a href="{{ route('farmer.logs.edit', $log) }}" class="btn btn-ghost btn-xs">Sửa</a>
                            <form method="POST" action="{{ route('farmer.logs.destroy', $log) }}" onsubmit="return confirm('Xóa nhật ký này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs text-error">Xóa</button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
