@extends('layouts.mobile')
@section('title', $tag->product->name)
@section('subtitle', 'Cổng truy xuất nguồn gốc')

@section('content')
<div class="space-y-4">

    @if($tag->batch->status->value === 'recalled')
    <div class="alert alert-error text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span><strong>Sản phẩm đã bị thu hồi.</strong> Vui lòng ngừng sử dụng và liên hệ nơi mua để được hỗ trợ đổi trả.</span>
    </div>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-5 h-5 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold text-success">Sản phẩm chính hãng</span>
            </div>
            <h1 class="text-lg font-bold">{{ $tag->product->name }}</h1>
            <p class="text-sm text-base-content/60">{{ $tag->product->brand?->name }} · Mã SKU: {{ $tag->product->sku }}</p>
            @if($tag->gs1_serial)
            <p class="text-xs text-base-content/50 mt-1">Mã Serial (đọc cho tổng đài khi cần hỗ trợ): <span class="font-mono font-semibold text-base-content">{{ $tag->gs1_serial }}</span></p>
            @endif
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-sm font-semibold mb-2">Thông tin lô sản xuất</h2>
            <dl class="text-sm space-y-1.5">
                <div class="flex justify-between"><dt class="text-base-content/50">Ngày sản xuất</dt><dd>{{ $tag->batch->mfg_date?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-base-content/50">Hạn sử dụng</dt><dd>{{ $tag->batch->exp_date->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-base-content/50">Đơn vị cung ứng</dt><dd>{{ $tag->batch->vendor->name }}</dd></div>
            </dl>
        </div>
    </div>

    @if($tag->product->compliances->isNotEmpty())
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-sm font-semibold mb-2">Hồ sơ pháp lý đang hiệu lực</h2>
            <ul class="text-sm space-y-2">
                @foreach($tag->product->compliances as $compliance)
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ $compliance->documentType->name }}{{ $compliance->document_number ? ' — Số: ' . $compliance->document_number : '' }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <p class="text-xs text-center text-base-content/40 pt-2">
        Sản phẩm này đã được quét {{ $scanCount }} lần.<br>
        Nếu bạn nghi ngờ đây là hàng giả, vui lòng liên hệ đơn vị phân phối.
    </p>

</div>
@endsection
