@extends('layouts.backend')
@section('title', $contract->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $contract->name }}
            <span class="badge {{ $contract->status->badgeClass() }} badge-sm">{{ $contract->status->label() }}</span>
            @if($contract->is_auto_renew)
            <span class="badge badge-info badge-sm gap-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Tự động gia hạn
            </span>
            @endif
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">{{ $contract->contract_number }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.contracts.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $contract)
        <a href="{{ route('backend.contracts.edit', $contract) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6 items-start">

    <div class="space-y-6">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-4">Thông tin hợp đồng</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Nhà cung cấp</dt>
                        <dd class="font-medium">{{ $contract->vendor?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Loại hợp đồng</dt>
                        <dd class="font-medium">{{ $contract->contractType?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Giá trị hợp đồng</dt>
                        <dd class="font-medium font-mono">
                            {{ $contract->total_value !== null ? number_format((float) $contract->total_value, 0, ',', '.') . ' đ' : '— (không định giá trị tổng)' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Thời hạn</dt>
                        <dd class="font-medium">
                            {{ $contract->start_date->format('d/m/Y') }}
                            →
                            {{ $contract->end_date?->format('d/m/Y') ?? 'Vô thời hạn' }}
                        </dd>
                    </div>
                    @if($contract->is_auto_renew)
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Chu kỳ gia hạn</dt>
                        <dd class="font-medium">{{ $contract->renewal_period_months }} tháng / lần</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

    </div>

    <div class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Chi tiết</p>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Tạo lúc</dt>
                        <dd>{{ $contract->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Cập nhật lúc</dt>
                        <dd>{{ $contract->updated_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

</div>
@endsection
