@extends('layouts.backend')
@section('title', 'Tra cứu vòng đời Serial')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-base-content">Tra cứu vòng đời Serial</h1>
    <p class="text-sm text-base-content/50 mt-0.5">Gõ mã hoặc quét súng mã vạch để xem toàn bộ lịch sử của một con tem — dùng cho đổi trả, kiểm tra tại quầy.</p>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('backend.serial-lookup.index') }}" class="flex items-end gap-2">
            <div class="form-control flex-1 max-w-md">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Mã serial / gs1_serial / URL quét được</span></label>
                <input type="text" name="code" value="{{ $code }}" autofocus placeholder="VD: TH00150" class="input input-bordered input-lg w-full font-mono" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Tra cứu</button>
        </form>
    </div>
</div>

@if($searched)
    @if(! $tag)
    <div class="alert alert-error py-3 px-4 text-sm">
        Không tìm thấy tem nào khớp với mã <strong class="font-mono">{{ $code }}</strong>.
    </div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6 items-start">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-4">Dòng thời gian</h2>
                <ul class="timeline timeline-vertical timeline-compact">
                    @foreach($events as $i => $event)
                    <li>
                        @if($i > 0)<hr @if($event['certain'] ?? true) class="bg-primary" @endif>@endif
                        <div class="timeline-start text-xs text-base-content/50 whitespace-nowrap">
                            {{ $event['date']?->format('d/m/Y H:i') ?? 'Không rõ ngày' }}
                        </div>
                        <div class="timeline-middle">
                            <div class="w-3 h-3 rounded-full {{ ($event['certain'] ?? true) ? 'bg-primary' : 'bg-base-300' }}"></div>
                        </div>
                        <div class="timeline-end timeline-box">
                            <p class="font-semibold text-sm">{{ $event['label'] }}</p>
                            <p class="text-xs text-base-content/60 mt-0.5">{{ $event['detail'] }}</p>
                        </div>
                        @if(!$loop->last)<hr @if($event['certain'] ?? true) class="bg-primary" @endif>@endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="text-base font-semibold mb-3">Thông tin tem</h2>
                    <dl class="text-sm space-y-2">
                        <div><dt class="text-base-content/50 text-xs">Trạng thái</dt><dd><span class="badge {{ $tag->status->badgeClass() }} badge-sm">{{ $tag->status->label() }}</span></dd></div>
                        <div><dt class="text-base-content/50 text-xs">gs1_serial</dt><dd class="font-mono">{{ $tag->gs1_serial ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Sản phẩm</dt><dd>{{ $tag->product?->name ?? '—' }}</dd></div>
                        <div>
                            <dt class="text-base-content/50 text-xs">Lô hàng</dt>
                            <dd>
                                @if($tag->batch)
                                <a href="{{ route('backend.batches.show', $tag->batch) }}" class="link link-primary font-mono">{{ $tag->batch->internal_batch_code }}</a>
                                @else
                                —
                                @endif
                            </dd>
                        </div>
                        <div><dt class="text-base-content/50 text-xs">Nhà cung cấp</dt><dd>{{ $tag->batch?->vendor?->name ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Hạn dùng</dt><dd>{{ $tag->batch?->exp_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            @if($tag->externalOrder)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="text-base font-semibold mb-3">Đơn hàng Sapo (để đổi trả)</h2>
                    <dl class="text-sm space-y-2">
                        <div><dt class="text-base-content/50 text-xs">Mã đơn hàng</dt><dd class="font-mono">{{ $tag->externalOrder->external_order_code }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Ngày đặt</dt><dd>{{ $tag->externalOrder->ordered_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Khách hàng</dt><dd>{{ $tag->externalOrder->customer?->name ?? '—' }} {{ $tag->externalOrder->customer?->phone ? '· '.$tag->externalOrder->customer->phone : '' }}</dd></div>
                    </dl>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
@endif
@endsection
