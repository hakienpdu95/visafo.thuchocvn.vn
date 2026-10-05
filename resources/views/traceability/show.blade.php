<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Truy xuất nguồn gốc — {{ $trace->productName }}</title>
    @vite(array_filter(['resources/css/app.css', count($trace->productImages) > 1 ? 'resources/js/modules/swiper.js' : null]), 'build/backend')
</head>
<body class="bg-gray-200/60 text-gray-800 antialiased">
@php
    $fmtAt = fn ($at) => $at ? $at->format($at->format('H:i') === '00:00' ? 'd/m/Y' : 'H:i, d/m/Y') : null;
    $expired = $trace->expDate?->isPast();
    // Nút pill dùng chung: hồ sơ tab VISAFO (partials/brand) và khối "Hồ sơ liên quan"
    $pillClass = 'inline-flex max-w-full items-center gap-1.5 rounded-full border border-gray-200 bg-gray-100 py-1.5 pl-2.5 pr-3 text-[13px] font-medium text-gray-800 transition hover:border-green-300 hover:bg-green-50 active:scale-95';
    // Icon (heroicons outline, path "d") dùng chung cho các dòng thông tin — xem partials/row.blade.php
    $icon = [
        'box'       => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'qr'        => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z',
        'tag'       => 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        'calendar'  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'hourglass' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'info'      => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'factory'   => 'M3 21h18M5 21V10l5 3V10l5 3V6l4-2v17',
        'building'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'pin'       => 'M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
        'map'       => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
        'phone'     => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
        'shield'    => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'seed'      => 'M12 21v-8m0 0c0-4 3-7 7-7 0 4-3 7-7 7zm0 0C12 9 9 6 5 6c0 4 3 7 7 7z',
        'farm'      => 'M12 21V11m0 0c-2.5 0-4.5-2-4.5-4.5V5c2.5 0 4.5 2 4.5 4.5M12 11c2.5 0 4.5-2 4.5-4.5V5C14 5 12 7 12 9.5M5 21h14',
        'harvest'   => 'M5 13l4 4L19 7M4 21h16',
        'warehouse' => 'M3 21V8l9-5 9 5v13M7 21v-8h10v8M7 17h10',
        'package'   => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'water'     => 'M12 3c-3 4.5-6 7.6-6 11a6 6 0 0012 0c0-3.4-3-6.5-6-11z',
        'truck'     => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
    ];
@endphp

<div class="max-w-md mx-auto bg-gray-100 min-h-screen pb-8">

    {{-- Header: logo + tên pháp nhân --}}
    <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-gray-200 bg-white/95 px-4 py-2.5 backdrop-blur">
        <img src="{{ asset('images/visafo-mark.png') }}" alt="{{ $trace->brand }}" class="h-10 w-10 shrink-0 object-contain">
        <div class="min-w-0">
            <p class="text-[13px] font-extrabold uppercase leading-tight tracking-wide text-green-800">{{ $trace->company['name'] }}</p>
            <p class="text-[11px] leading-tight text-gray-500">Truy xuất nguồn gốc · TT 02/2024/TT-BKHCN</p>
        </div>
    </header>

    @unless($trace->status->isActive())
    {{-- Tem bị thu hồi / đánh dấu lỗi: hiện cảnh báo đỏ thay cho thông tin bình thường --}}
    <main class="space-y-4 px-4 pt-4">
        <section class="overflow-hidden rounded-2xl border-2 border-red-500 bg-white shadow-md">
            <div class="bg-red-600 px-4 py-3 text-white">
                <p class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    Cảnh báo
                </p>
            </div>
            <div class="space-y-3 p-5 text-center">
                <h2 class="text-2xl font-bold leading-snug text-red-600">
                    @if($trace->status === \Modules\SalesOrder\Enums\PrintLogStatus::Recalled)
                        Sản phẩm này đã bị thu hồi
                    @elseif($trace->status === \Modules\SalesOrder\Enums\PrintLogStatus::Revoked)
                        Mã truy xuất này đã bị hủy
                    @else
                        Sản phẩm này đang được kiểm tra do phát hiện lỗi
                    @endif
                </h2>
                @if($trace->status === \Modules\SalesOrder\Enums\PrintLogStatus::Revoked)
                <p class="text-sm text-gray-700">Tem mang mã này đã được thay bằng tem mới. Nếu sản phẩm bạn đang cầm vẫn dán tem có mã này, vui lòng liên hệ đơn vị bán hàng để xác minh.</p>
                @else
                <p class="text-sm text-gray-700">Vui lòng <strong>không sử dụng</strong> và liên hệ đơn vị bán hàng để được hỗ trợ.</p>
                @endif
                @if($trace->statusReason)
                <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><span class="font-semibold">Lý do:</span> {{ $trace->statusReason }}</p>
                @endif
                <dl class="divide-y divide-gray-100 border-t border-gray-100 pt-2 text-left text-sm">
                    <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Sản phẩm</dt><dd class="text-right font-semibold">{{ $trace->productName }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Mã TXNG</dt><dd class="break-all text-right font-mono font-semibold text-red-600">{{ strtoupper($trace->traceCode) }}</dd></div>
                </dl>
                @if($trace->company['hotline'] !== '')
                <a href="tel:{{ preg_replace('/\s+/', '', $trace->company['hotline']) }}" class="inline-block rounded-full bg-red-600 px-5 py-2 text-sm font-semibold text-white">Gọi hotline: {{ $trace->company['hotline'] }}</a>
                @endif
            </div>
        </section>
    </main>
    @else
    <main class="px-4">

        {{-- Tabs: Sản phẩm / Thương hiệu (dính dưới header khi cuộn) --}}
        <nav class="sticky top-[61px] z-20 -mx-4 bg-gray-100/95 px-4 py-3 backdrop-blur">
            <div role="tablist" aria-label="Nội dung truy xuất" class="grid grid-cols-3 gap-1 rounded-full bg-white p-1 shadow-sm ring-1 ring-black/5">
                @foreach(['product' => ['📦', 'Sản phẩm'], 'brand' => ['🏢', 'VISAFO'], 'review' => ['⭐', 'Đánh giá']] as $tab => [$tabEmoji, $tabLabel])
                <button type="button" role="tab" id="trace-tab-{{ $tab }}" data-trace-tab="{{ $tab }}" aria-controls="trace-panel-{{ $tab }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}"
                        class="flex items-center justify-center gap-1 whitespace-nowrap rounded-full py-2 text-sm font-semibold text-gray-500 transition aria-selected:bg-green-600 aria-selected:text-white aria-selected:shadow">
                    <span aria-hidden="true">{{ $tabEmoji }}</span>
                    {{ $tabLabel }}
                </button>
                @endforeach
            </div>
        </nav>

        {{-- TAB 1: SẢN PHẨM --}}
        <div id="trace-panel-product" role="tabpanel" aria-labelledby="trace-tab-product" data-trace-panel="product" class="space-y-4">

        {{-- 1. Hero: ảnh sản phẩm (slider) + thông tin tổng quan --}}
        <section class="rounded-md bg-white p-3 shadow-sm ring-1 ring-black/5">
            <div class="relative aspect-square overflow-hidden rounded-md bg-gray-100">
                @if($trace->productImages)
                    {{-- Ảnh chính luôn ở index 0 (slide đầu) — thứ tự đã sắp sẵn ở backend --}}
                    <div id="trace-product-swiper" class="swiper h-full w-full">
                        <div class="swiper-wrapper">
                            @foreach($trace->productImages as $i => $imageUrl)
                            <div class="swiper-slide">
                                <img src="{{ $imageUrl }}" alt="{{ $trace->productName }}{{ $i ? ' — ảnh ' . ($i + 1) : '' }}"
                                     class="h-full w-full object-cover" @if($i) loading="lazy" @endif data-trace-img-fallback>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @else
                @include('traceability.partials.image-placeholder', ['size' => 'lg'])
                @endif

                {{-- Nút nổi: Chia sẻ / Yêu thích --}}
                <div class="absolute right-3 top-3 z-10 flex flex-col gap-2">
                    <button type="button" data-trace-share aria-label="Chia sẻ"
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-md ring-1 ring-black/5 backdrop-blur transition active:scale-95">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    </button>
                    <button type="button" data-trace-fav aria-label="Yêu thích" aria-pressed="false"
                            class="group flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-md ring-1 ring-black/5 backdrop-blur transition active:scale-95 aria-pressed:text-red-500">
                        <svg class="h-5 w-5 group-aria-pressed:fill-current" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </button>
                </div>

                {{-- Pill số thứ tự ảnh + chấm (chỉ khi có nhiều ảnh) --}}
                @if(count($trace->productImages) > 1)
                <div class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 items-center gap-2 rounded-full bg-black/55 px-3 py-1 text-xs font-medium text-white backdrop-blur">
                    <span class="tabular-nums"><span data-trace-current>1</span>/{{ count($trace->productImages) }}</span>
                    <span class="flex items-center gap-1">
                        @foreach($trace->productImages as $i => $_)
                        <span data-trace-dot class="h-1.5 rounded-full transition-all {{ $i === 0 ? 'w-3 bg-white' : 'w-1.5 bg-white/50' }}"></span>
                        @endforeach
                    </span>
                </div>
                @endif
            </div>

            <div class="px-2 pb-2 pt-4">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon['shield'] }}"/></svg>
                    Lô hàng được {{ $trace->brand }} ghi nhận &amp; kiểm soát
                </span>
                <h1 class="mt-3 text-[26px] font-bold leading-tight text-gray-900">{{ $trace->productName }}</h1>
                @if($trace->categoryName)
                <p class="mt-1 text-sm font-medium text-orange-600">{{ $trace->categoryName }}</p>
                @endif
                {{-- Quy cách · Mã lô · Mã TXNG --}}
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach(array_filter([
                        ['package', 'Quy cách: ' . $trace->weight, false],
                        $trace->batchCode ? ['tag', 'Lô: ' . $trace->batchCode, true] : null,
                        ['qr', 'TX: ' . strtoupper($trace->traceCode), true],
                    ]) as [$pillIcon, $pillText, $pillMono])
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 {{ $pillMono ? 'font-mono' : '' }}">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon[$pillIcon] }}"/></svg>
                        {{ $pillText }}
                    </span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 2. Thông tin sản phẩm & lô: lưới 2 cột (1 cột khi màn hình < 340px) + NSX / thông tin in bổ sung bên dưới --}}
        <section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Thông tin sản phẩm &amp; lô
            </h3>
            <dl class="grid grid-cols-1 gap-3 min-[340px]:grid-cols-2">
                @foreach([
                    // Ô hẹp thì xuống dòng trước "• H:i" (không tách giữa ngày)
                    ['Ngày đóng gói', $trace->packedAt ? new \Illuminate\Support\HtmlString('<span class="whitespace-nowrap">' . $trace->packedAt->format('d/m/Y') . '</span> <span class="whitespace-nowrap">• ' . $trace->packedAt->format('H:i') . '</span>') : null, false],
                    ['Hạn sử dụng', $trace->expDate?->format('d/m/Y'), $expired],
                    ['Khối lượng lô', $trace->batchQuantity, false],
                    ['Mã sản phẩm', $trace->productSku, false],
                ] as [$cellLabel, $cellValue, $cellDanger])
                <div class="rounded-lg bg-[#f8f9fa] px-3.5 py-3">
                    <dt class="text-xs text-gray-500">{{ $cellLabel }}</dt>
                    @if(filled($cellValue))
                    <dd class="mt-1 text-[15px] font-semibold leading-snug {{ $cellDanger ? 'text-red-600' : 'text-slate-900' }}">
                        {{ $cellValue }}
                        @if($cellDanger)<span class="ml-1 whitespace-nowrap rounded bg-red-100 px-1.5 py-0.5 align-middle text-[11px] font-medium">Hết hạn</span>@endif
                    </dd>
                    @else
                    <dd class="mt-1 text-sm italic text-gray-400">Đang cập nhật</dd>
                    @endif
                </div>
                @endforeach
            </dl>
            {{-- NSX (bắt buộc theo TT 02/2024) + thông tin động (EAV) nhập lúc in tem: Bảo quản, HDSD... --}}
            <ul class="mt-2 divide-y divide-gray-100">
                @include('traceability.partials.row', ['ic' => 'calendar', 'label' => 'Thời gian sản xuất (NSX)',
                    'value' => $trace->mfgDate?->format('d/m/Y')])
                @foreach($trace->attributes as $attr)
                @include('traceability.partials.row', ['ic' => 'info', 'label' => $attr['key'], 'value' => $attr['value'] !== '' ? $attr['value'] : '—'])
                @endforeach
            </ul>
        </section>

        {{-- 2b. Nguồn gốc sản phẩm: chỉ khi lô nhập đã liên kết lô canh tác (product_batches.farming_batch_id) --}}
        @if($trace->location)
        @php $loc = $trace->location; @endphp
        <section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Nguồn gốc sản phẩm
            </h3>
            <div class="ml-1.5 border-l-2 border-green-200 pl-4">
                <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-xs text-green-700 ring-1 ring-green-200">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Đã liên kết nguồn
                </span>
                <p class="mt-2 text-md font-medium leading-snug text-green-700">{{ $loc['isOwn'] ? 'Vùng trồng của ' . $trace->brand : $loc['name'] }}</p>
                <dl class="mt-2 space-y-1.5 text-sm leading-snug">
                    @foreach(array_filter([
                        $loc['isOwn'] ? ['Khu trồng', $loc['name']] : null,
                        ['Vùng sản xuất', $loc['address']],
                        ['Nhà cung cấp', $loc['isOwn'] ? null : $loc['vendorName']],
                        ['Ngày thu hoạch', $loc['harvestedAt']?->format('d/m/Y')],
                        ['Diện tích', $loc['area'] !== null ? rtrim(rtrim(number_format($loc['area'], 2, ',', '.'), '0'), ',') . ' ha' : null],
                        ['Nguồn nước', $loc['waterSource']],
                    ], fn ($row) => $row !== null && filled($row[1])) as [$rowLabel, $rowValue])
                    <div><dt class="inline text-gray-600">{{ $rowLabel }}:</dt> <dd class="inline font-medium text-gray-900">{{ $rowValue }}</dd></div>
                    @endforeach
                    @if($loc['preSeason'] !== null || $loc['harvestApprovedAt'])
                    <div>
                        <dt class="inline text-gray-600">QC vùng trồng:</dt>
                        <dd class="inline font-medium">
                            @if($loc['preSeason'] !== null)
                            <span class="{{ $loc['preSeason'] ? 'text-green-700' : 'text-red-600' }}">{{ $loc['preSeason'] ? '✓' : '✕' }} trước vụ</span>
                            @endif
                            @if($loc['harvestApprovedAt'])
                            <span class="whitespace-nowrap text-green-700">{{ $loc['preSeason'] !== null ? '• ' : '' }}✓ phê duyệt thu hoạch</span>
                            @endif
                        </dd>
                    </div>
                    @endif
                    {{-- Mã nguồn = mã lô canh tác, Địa điểm = mã vùng trồng (mã truy vết địa điểm theo TT 02/2024) --}}
                    <div class="text-gray-600">
                        <span class="whitespace-nowrap">Mã nguồn: <span class="font-mono text-[13px] font-medium text-gray-900">{{ $loc['batchCode'] }}</span></span>
                        <span class="whitespace-nowrap">• Địa điểm: <span class="font-mono text-[13px] font-medium text-gray-900">{{ $loc['code'] }}</span></span>
                    </div>
                </dl>
            </div>
        </section>
        @endif

        {{-- 2c. Hành trình hàng hóa: 5 mốc tóm tắt; trục + chấm vẽ bằng ::before/::after của <li> --}}
        <section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Hành trình hàng hóa
            </h3>
            <ul>
                @foreach($trace->journey as $step)
                <li class="relative pb-5 pl-7 last:pb-0
                           before:absolute before:left-[5px] before:top-3 before:bottom-0 before:w-0.5 before:bg-green-200 last:before:hidden
                           after:absolute after:left-0 after:top-1 after:h-3 after:w-3 after:rounded-full after:ring-4
                           {{ $step['done'] ? 'after:bg-green-600 after:ring-green-100' : 'after:bg-gray-300 after:ring-gray-100' }}">
                    <p class="text-[15px] font-medium leading-snug {{ $step['done'] ? 'text-slate-900' : 'text-gray-400' }}">{{ $step['title'] }}</p>
                    @if($step['time'])
                    <p class="mt-0.5 text-xs font-medium text-gray-500">{{ $step['time'] }}</p>
                    @endif
                    @if($step['meta'])
                    <p class="mt-0.5 text-[13px] leading-snug {{ $step['ok'] === false ? 'text-red-600' : 'text-gray-400' }}">
                        @if($step['ok'] === true)<span class="font-semibold text-green-600">✓</span>@elseif($step['ok'] === false)<span class="font-semibold">✕</span>@endif
                        {{ $step['meta'] }}
                    </p>
                    @endif
                </li>
                @endforeach
            </ul>
        </section>

        {{-- 4b. Kiểm soát chất lượng: 3 khâu QC của doanh nghiệp + kết luận lô --}}
        <section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Kiểm soát chất lượng
            </h3>
            <dl>
                @foreach($trace->qualityChecks as $qc)
                <div class="flex items-baseline justify-between gap-3 py-2">
                    <dt class="text-sm text-gray-700">{{ $qc['label'] }}</dt>
                    @if($qc['result'] === 'pass')
                    <dd class="shrink-0 text-sm font-semibold text-green-700" @if($qc['at']) title="{{ $fmtAt($qc['at']) }}" @endif>✓ Đạt</dd>
                    @elseif($qc['result'] === 'fail')
                    <dd class="shrink-0 text-sm font-semibold text-red-600" @if($qc['at']) title="{{ $fmtAt($qc['at']) }}" @endif>✕ Không đạt</dd>
                    @else
                    <dd class="shrink-0 text-sm italic text-gray-400">Đang cập nhật</dd>
                    @endif
                </div>
                @endforeach
                <div class="mt-1 flex items-baseline justify-between gap-3 border-t border-gray-200 pt-3">
                    <dt class="text-sm text-gray-700">Kết luận lô hàng</dt>
                    @if($trace->qcConclusion === 'pass')
                    <dd class="shrink-0 text-sm font-medium uppercase text-green-700">Đủ ĐK xuất</dd>
                    @elseif($trace->qcConclusion === 'fail')
                    <dd class="shrink-0 text-sm font-medium uppercase text-red-600">Không đạt</dd>
                    @else
                    <dd class="shrink-0 text-sm italic text-gray-400">Đang cập nhật</dd>
                    @endif
                </div>
            </dl>
        </section>

        {{-- 4c. Giao vận & điểm nhận (chỉ khi đã xuất kho). Tên điểm nhận đã che, địa chỉ chỉ cấp phường/quận + tỉnh — xử lý ở backend --}}
        @if($trace->delivery)
        @php $dl = $trace->delivery; @endphp
        <section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Giao vận &amp; điểm nhận
            </h3>
            <div class="rounded-lg bg-[#f8f9fa] p-3.5 text-sm">
                <p class="font-bold text-slate-900">🏢 Kho {{ $trace->brand }}</p>
                <p class="mt-0.5 text-gray-600">{{ implode(' • ', array_filter([$dl['warehouse'], 'Xuất ' . $dl['shippedAt']->format('H:i • d/m/Y')])) }}</p>

                <p class="my-1.5 pl-2 text-lg leading-none text-green-600" aria-hidden="true">↓</p>

                <p class="font-bold text-slate-900">🚚 Đơn giao: <span class="font-mono">{{ $dl['code'] }}</span></p>
                <p class="mt-0.5 {{ $dl['deliveredAt'] ? 'text-green-700' : 'text-amber-600' }}">{{ $dl['status'] }}</p>

                <p class="my-1.5 pl-2 text-lg leading-none text-green-600" aria-hidden="true">↓</p>

                <div class="rounded-lg border border-green-200 bg-white p-3">
                    <p class="font-bold text-slate-900">📍 {{ $dl['recipient'] ?? 'Điểm nhận' }}</p>
                    @if($dl['deliveredAt'])
                    <p class="mt-0.5 font-medium text-green-700">✓ Đã nhận hàng • {{ $dl['deliveredAt']->format('H:i • d/m/Y') }}</p>
                    @else
                    <p class="mt-0.5 text-gray-500">Chờ nhận hàng</p>
                    @endif
                    @if($dl['area'])
                    <p class="mt-0.5 text-gray-600">Điểm giao: {{ $dl['area'] }}</p>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- 6. Đơn vị cung ứng: doanh nghiệp chủ quản kiểm soát chuỗi (hồ sơ trụ sở chính / config trace) --}}
        <section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Đơn vị cung ứng
            </h3>
            <div class="ml-1.5 border-l-2 border-green-200 pl-4 text-sm leading-snug">
                <p class="font-bold uppercase text-green-700">{{ $trace->company['name'] }}</p>
                <dl class="mt-2 space-y-1.5 text-gray-700">
                    @if($trace->company['supplyChainRole'])
                    {{-- Soạn ở dashboard/internal-compliance (Jodit), đã qua RichHtmlSanitizer --}}
                    <div>
                        <dt>Vai trò:</dt>
                        <dd class="wysiwyg-content mt-1">{!! $trace->company['supplyChainRole'] !!}</dd>
                    </div>
                    @else
                    <div><dt class="inline">Vai trò:</dt> <dd class="inline font-medium text-gray-900">Tiếp nhận • QC • Sơ chế/đóng gói • Cung ứng</dd></div>
                    @endif
                    @if($trace->company['address'])
                    <div><dt class="inline">Địa chỉ:</dt> <dd class="inline font-medium text-gray-900">{{ $trace->company['address'] }}</dd></div>
                    @endif
                    @if($trace->company['taxCode'] !== '')
                    <div><dt class="inline">MST:</dt> <dd class="inline font-mono font-medium text-gray-900">{{ $trace->company['taxCode'] }}</dd></div>
                    @endif
                    @if($trace->company['hotline'] !== '')
                    <div><dt class="inline">Hotline:</dt> <dd class="inline"><a href="tel:{{ preg_replace('/\s+/', '', $trace->company['hotline']) }}" class="font-semibold text-green-700">{{ $trace->company['hotline'] }}</a></dd></div>
                    @endif
                </dl>
            </div>
        </section>

        {{-- 7. Hồ sơ liên quan: nút mở tệp hồ sơ (partials/doc-pill: 1 tệp → tab mới, nhiều tệp → lightbox, chưa có → xám) + nút sang tab VISAFO --}}
        <section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Hồ sơ liên quan
            </h3>
            <div class="flex flex-wrap gap-2">
                {{-- Phiếu QC / phiếu lô / giao nhận chưa có tệp biên bản đính kèm trong hệ thống → nút xám (giữ cấu trúc) --}}
                @include('traceability.partials.doc-pill', ['label' => 'Hồ sơ nguồn', 'iconHtml' => '🌱',
                    'files' => collect($trace->sourceDocuments)->flatMap(fn ($d) => $d['files'])->values()->all(),
                    'caption' => collect($trace->sourceDocuments)->pluck('caption')->implode(' | ') ?: 'Hồ sơ nguồn'])
                @include('traceability.partials.doc-pill', ['label' => 'Phiếu QC', 'iconHtml' => '📋', 'files' => []])
                @include('traceability.partials.doc-pill', ['label' => 'Phiếu lô', 'iconHtml' => '📦', 'files' => []])
                @include('traceability.partials.doc-pill', ['label' => 'Giao nhận', 'iconHtml' => '🚚', 'files' => []])
                <button type="button" data-trace-goto-tab="brand" class="{{ $pillClass }}">🏢 Hồ sơ {{ $trace->brand }} <span aria-hidden="true">›</span></button>
            </div>
        </section>

        </div>

        {{-- TAB 2: THƯƠNG HIỆU --}}
        <div id="trace-panel-brand" role="tabpanel" aria-labelledby="trace-tab-brand" data-trace-panel="brand" class="space-y-4" hidden>
            @include('traceability.partials.brand')
        </div>

        {{-- TAB 3: ĐÁNH GIÁ (chưa có tính năng đánh giá — khung chờ) --}}
        <div id="trace-panel-review" role="tabpanel" aria-labelledby="trace-tab-review" data-trace-panel="review" class="space-y-4" hidden>
            <section class="rounded-md bg-white p-6 text-center shadow-sm ring-1 ring-black/5">
                <p class="text-3xl" aria-hidden="true">⭐</p>
                <h3 class="mt-2 text-base font-semibold text-gray-900">Đánh giá sản phẩm</h3>
                <p class="mt-1 text-sm text-gray-500">Tính năng đánh giá đang được hoàn thiện. Cảm ơn bạn đã tin dùng sản phẩm của {{ $trace->brand }}!</p>
            </section>
        </div>

        <p class="px-2 pt-6 text-center text-xs leading-relaxed text-gray-400">
            Thông tin được truy xuất từ hệ thống {{ $trace->brand }} theo Thông tư 02/2024/TT-BKHCN.<br>
            Mã TXNG chỉ có giá trị cho đúng lần đóng gói in trên tem.
        </p>
    </main>
    @endunless
</div>
{{-- Lightbox xem bản scan (các tệp của một hồ sơ xếp dọc, cuộn được). Đặt ngoài các tab: nút mở nằm ở cả tab Sản phẩm lẫn VISAFO —
     nếu nằm trong panel đang ẩn thì lightbox mở "vô hình" mà trang vẫn bị khóa cuộn. --}}
<div data-trace-lightbox-root hidden class="fixed inset-0 z-50 flex flex-col bg-black/90" role="dialog" aria-modal="true" aria-label="Xem hồ sơ">
    <div class="flex justify-end p-3">
        <button type="button" data-trace-lightbox-close aria-label="Đóng"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div data-trace-lightbox-body class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain px-3 pb-6"></div>
</div>
{{-- Ảnh sản phẩm lỗi URL (404, hết hạn) → thay bằng ảnh placeholder cùng kích thước khung --}}
<template id="trace-img-placeholder-lg">@include('traceability.partials.image-placeholder', ['size' => 'lg'])</template>
<template id="trace-img-placeholder-sm">@include('traceability.partials.image-placeholder', ['size' => 'sm'])</template>
<script>
    document.querySelectorAll('img[data-trace-img-fallback]').forEach((img) => {
        const swap = () => img.replaceWith(document.getElementById('trace-img-placeholder-' + (img.dataset.traceImgFallback || 'lg')).content.cloneNode(true));
        if (img.complete && img.naturalWidth === 0) swap(); else img.addEventListener('error', swap, { once: true });
    });
</script>
@if(count($trace->productImages) > 1)
<script type="module">
    // Pagination tự vẽ (pill "1/N" + chấm) thay cho pagination mặc định của Swiper
    const current = document.querySelector('[data-trace-current]');
    const dots = document.querySelectorAll('[data-trace-dot]');
    initSwiper('#trace-product-swiper', {
        navigation: false,
        pagination: false,
        on: {
            slideChange(sw) {
                current.textContent = sw.realIndex + 1;
                dots.forEach((d, i) => {
                    d.classList.toggle('w-3', i === sw.realIndex);
                    d.classList.toggle('bg-white', i === sw.realIndex);
                    d.classList.toggle('w-1.5', i !== sw.realIndex);
                    d.classList.toggle('bg-white/50', i !== sw.realIndex);
                });
            },
        },
    });
</script>
@endif
<script>
    (() => {
        const toast = (msg) => {
            const el = document.createElement('div');
            el.textContent = msg;
            el.className = 'fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-full bg-gray-900/90 px-4 py-2 text-sm text-white shadow-lg';
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 2000);
        };

        document.querySelector('[data-trace-share]')?.addEventListener('click', async () => {
            const data = { title: document.title, text: @js($trace->productName . ' — truy xuất nguồn gốc'), url: location.href };
            try {
                if (navigator.share) { await navigator.share(data); return; }
                await navigator.clipboard.writeText(location.href);
                toast('Đã sao chép liên kết');
            } catch (e) {
                if (e?.name !== 'AbortError') toast('Không chia sẻ được, hãy sao chép địa chỉ trang');
            }
        });

        // Tabs Sản phẩm / VISAFO / Đánh giá — nhớ tab qua hash (#thuong-hieu, #danh-gia) để chia sẻ link đúng tab
        const tabHash = { brand: '#thuong-hieu', review: '#danh-gia' };
        const tabs = [...document.querySelectorAll('[data-trace-tab]')];
        const showTab = (name, focus = false) => {
            tabs.forEach((t) => {
                const on = t.dataset.traceTab === name;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                if (on && focus) t.focus();
                document.querySelector(`[data-trace-panel="${t.dataset.traceTab}"]`).hidden = !on;
            });
            history.replaceState(null, '', tabHash[name] ?? location.pathname + location.search);
        };
        tabs.forEach((t, i) => {
            t.addEventListener('click', () => { showTab(t.dataset.traceTab); window.scrollTo({ top: 0 }); });
            t.addEventListener('keydown', (e) => {
                if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
                showTab(tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length].dataset.traceTab, true);
            });
        });
        const hashTab = Object.keys(tabHash).find((k) => tabHash[k] === location.hash);
        if (tabs.length && hashTab) showTab(hashTab);
        // Nút "Hồ sơ VISAFO" (khối Hồ sơ liên quan): chuyển sang tab VISAFO
        document.querySelectorAll('[data-trace-goto-tab]').forEach((b) => b.addEventListener('click', () => {
            showTab(b.dataset.traceGotoTab);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }));

        // Lightbox xem ảnh hồ sơ doanh nghiệp
        const box = document.querySelector('[data-trace-lightbox-root]');
        const boxBody = box?.querySelector('[data-trace-lightbox-body]');
        const closeBox = () => { box.hidden = true; boxBody.replaceChildren(); document.body.style.overflow = ''; };
        const spinner = '<svg class="h-4 w-4 animate-spin text-gray-500" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>';
        // Tải trước một ảnh (timeout 20s) — lightbox chỉ mở khi mọi ảnh đã về, không mở lên màn đen chờ mạng
        const preload = (src) => new Promise((resolve, reject) => {
            const im = new Image();
            const timer = setTimeout(() => reject(new Error('timeout')), 20000);
            im.onload = () => { clearTimeout(timer); resolve(im); };
            im.onerror = () => { clearTimeout(timer); reject(new Error('load')); };
            im.src = src;
        });
        document.querySelectorAll('button[data-trace-lightbox]').forEach((btn) => btn.addEventListener('click', async () => {
            if (btn.disabled) return; // chống bấm liên tục khi đang tải
            const slides = JSON.parse(btn.dataset.traceLightbox);
            const icon = btn.querySelector('[data-pill-icon]');
            const iconHtml = icon?.innerHTML;
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            if (icon) icon.innerHTML = spinner;
            try {
                const images = await Promise.all(slides.map(({ img }) => preload(img)));
                const caption = document.createElement('p');
                caption.className = 'mx-auto w-full max-w-2xl text-center text-sm font-medium text-white';
                caption.textContent = btn.dataset.traceCaption || '';
                boxBody.replaceChildren(...(caption.textContent ? [caption] : []), ...slides.map(({ pdf }, i) => {
                    const fig = document.createElement('figure');
                    fig.className = 'mx-auto w-full max-w-2xl';
                    const img = images[i];
                    img.alt = btn.dataset.traceCaption || '';
                    img.className = 'w-full rounded bg-white';
                    fig.append(img);
                    if (pdf) {
                        const a = document.createElement('a');
                        a.href = pdf; a.target = '_blank'; a.rel = 'noopener noreferrer';
                        a.textContent = 'Mở bản PDF đầy đủ';
                        a.className = 'mt-2 block rounded-full bg-white/15 py-2 text-center text-sm font-semibold text-white';
                        fig.append(a);
                    }
                    return fig;
                }));
                box.hidden = false;
                document.body.style.overflow = 'hidden';
            } catch {
                toast('Tài liệu tạm thời không khả dụng');
            } finally {
                btn.disabled = false;
                btn.removeAttribute('aria-busy');
                if (icon) icon.innerHTML = iconHtml;
            }
        }));
        box?.querySelector('[data-trace-lightbox-close]').addEventListener('click', closeBox);
        box?.addEventListener('click', (e) => { if (e.target === box || e.target === boxBody) closeBox(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && box && !box.hidden) closeBox(); });

        // Yêu thích: chỉ lưu trên trình duyệt của người xem (không cần đăng nhập)
        const fav = document.querySelector('[data-trace-fav]');
        const key = 'trace-fav:' + @js($trace->traceCode);
        const read = () => { try { return localStorage.getItem(key) === '1'; } catch { return false; } };
        const paint = (on) => fav?.setAttribute('aria-pressed', on ? 'true' : 'false');
        paint(read());
        fav?.addEventListener('click', () => {
            const on = !read();
            try { on ? localStorage.setItem(key, '1') : localStorage.removeItem(key); } catch {}
            paint(on);
            toast(on ? 'Đã thêm vào yêu thích' : 'Đã bỏ yêu thích');
        });
    })();
</script>
</body>
</html>
