<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Truy xuất nguồn gốc — {{ $trace->productName }}</title>
    @vite(['resources/css/app.css'], 'build/backend')
</head>
<body class="bg-gray-100 text-gray-800 antialiased">
@php
    $fmtAt = fn ($at) => $at ? $at->format($at->format('H:i') === '00:00' ? 'd/m/Y' : 'H:i, d/m/Y') : null;
    $expired = $trace->expDate?->isPast();
@endphp

<div class="max-w-md mx-auto bg-gray-50 min-h-screen shadow-sm pb-8">

    {{-- Header --}}
    <header class="bg-gradient-to-b {{ $trace->status->isActive() ? 'from-green-700 to-green-600' : 'from-red-700 to-red-600' }} px-5 pt-5 pb-14 text-white">
        <div class="flex items-center justify-between">
            <span class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 shadow-sm">
                <img src="{{ asset('images/visafo-logo.svg') }}" alt="VISAFO" class="h-6 w-auto">
            </span>
            @if($trace->status->isActive())
            <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-medium">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Đã xác thực
            </span>
            @else
            <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-semibold text-red-600">Không còn hiệu lực</span>
            @endif
        </div>
        <h1 class="mt-5 text-lg font-semibold tracking-wide uppercase">Truy xuất nguồn gốc</h1>
        <p class="text-sm text-green-100">Thông tin sản phẩm theo Thông tư 02/2024/TT-BKHCN</p>
    </header>

    @unless($trace->status->isActive())
    {{-- Tem bị thu hồi / đánh dấu lỗi: hiện cảnh báo đỏ thay cho thông tin bình thường --}}
    <main class="-mt-10 space-y-4 px-4">
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
                    @else
                        Sản phẩm này đang được kiểm tra do phát hiện lỗi
                    @endif
                </h2>
                <p class="text-sm text-gray-700">Vui lòng <strong>không sử dụng</strong> và liên hệ đơn vị bán hàng để được hỗ trợ.</p>
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
    <main class="-mt-10 space-y-4 px-4">

        {{-- Block 1: Sản phẩm --}}
        <section class="overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-black/5">
            <div class="flex h-56 items-center justify-center bg-green-50">
                @if($trace->productImage)
                    <img src="{{ $trace->productImage }}" alt="{{ $trace->productName }}" class="h-full w-full object-cover"
                         onerror="this.remove(); document.getElementById('trace-noimg').classList.remove('hidden')">
                @endif
                <div id="trace-noimg" class="{{ $trace->productImage ? 'hidden' : '' }} flex flex-col items-center text-green-300">
                    <svg class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="mt-1 text-xs">Chưa có hình ảnh</span>
                </div>
            </div>
            <div class="p-4">
                @if($trace->categoryName)
                <span class="inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">{{ $trace->categoryName }}</span>
                @endif
                <h2 class="mt-2 text-xl font-bold leading-snug text-gray-900">{{ $trace->productName }}</h2>
            </div>
        </section>

        {{-- Block 2: Thông tin chính --}}
        <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Thông tin chính
            </h3>
            <dl class="divide-y divide-gray-100 text-sm">
                <div class="flex items-start justify-between gap-4 py-2.5">
                    <dt class="shrink-0 text-gray-500">Mã TXNG</dt>
                    <dd class="break-all text-right font-mono font-semibold text-green-700">{{ strtoupper($trace->traceCode) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 py-2.5">
                    <dt class="shrink-0 text-gray-500">Khối lượng</dt>
                    <dd class="text-right font-semibold">{{ $trace->weight }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 py-2.5">
                    <dt class="shrink-0 text-gray-500">Ngày sản xuất (NSX)</dt>
                    <dd class="text-right font-medium">{{ $trace->mfgDate?->format('d/m/Y') ?? 'Đang cập nhật' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 py-2.5">
                    <dt class="shrink-0 text-gray-500">Hạn sử dụng (HSD)</dt>
                    <dd class="text-right font-medium {{ $expired ? 'text-red-600' : '' }}">
                        {{ $trace->expDate?->format('d/m/Y') ?? 'Đang cập nhật' }}
                        @if($expired)<span class="ml-1 rounded bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-600">Hết hạn</span>@endif
                    </dd>
                </div>
                {{-- Thông tin động (EAV): Tiêu chuẩn, Bảo quản, HDSD... --}}
                @foreach($trace->attributes as $attr)
                <div class="flex items-start justify-between gap-4 py-2.5">
                    <dt class="shrink-0 text-gray-500">{{ $attr['key'] }}</dt>
                    <dd class="text-right font-medium">{{ $attr['value'] !== '' ? $attr['value'] : '—' }}</dd>
                </div>
                @endforeach
            </dl>
        </section>

        {{-- Block 3: Nguồn gốc & Đơn vị SXKD --}}
        <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Nguồn gốc &amp; Đơn vị SXKD
            </h3>
            <div class="space-y-4 text-sm">
                <div class="flex gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </span>
                    <div>
                        <p class="text-xs text-gray-500">Đơn vị đóng gói / phân phối</p>
                        <p class="font-semibold text-gray-900">{{ $trace->company['name'] }}</p>
                        <p class="text-gray-600">{{ $trace->company['address'] !== '' ? $trace->company['address'] : 'Địa chỉ đang cập nhật' }}</p>
                        @if($trace->company['hotline'] !== '')
                        <a href="tel:{{ preg_replace('/\s+/', '', $trace->company['hotline']) }}" class="mt-0.5 inline-block font-medium text-green-700">Hotline: {{ $trace->company['hotline'] }}</a>
                        @endif
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21c-4-4-7-7.5-7-11a7 7 0 1114 0c0 3.5-3 7-7 11z"/><circle cx="12" cy="10" r="2.5" stroke-width="1.8"/></svg>
                    </span>
                    <div>
                        <p class="text-xs text-gray-500">Nguồn cung / Vùng trồng</p>
                        <p class="font-semibold text-gray-900">{{ $trace->supplierName }}</p>
                        @if($trace->batchCode)
                        <p class="text-gray-600">Lô hàng: <span class="font-mono">{{ $trace->batchCode }}</span></p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Block 4: Nhật ký TXNG (Vertical Timeline) --}}
        <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
                <span class="h-4 w-1 rounded bg-green-600"></span> Nhật ký truy xuất
            </h3>
            <ol class="relative ml-2 border-l-2 border-green-200">
                @foreach($trace->timeline as $step)
                <li class="relative pb-6 pl-6 last:pb-0">
                    <span class="absolute -left-[9px] top-0.5 flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white
                                 {{ $step['done'] ? 'bg-green-600' : 'border-2 border-green-500 bg-white' }}">
                        @if($step['done'])
                        <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </span>
                    <p class="text-sm font-semibold {{ $step['done'] ? 'text-gray-900' : 'text-gray-500' }}">{{ $step['title'] }}</p>
                    @if($step['at'])
                    <time class="mt-0.5 block text-xs font-medium text-green-700">{{ $fmtAt($step['at']) }}</time>
                    @endif
                    <p class="mt-1 text-sm text-gray-600">{{ $step['description'] }}</p>
                </li>
                @endforeach
            </ol>
        </section>

        <p class="px-2 pt-2 text-center text-xs leading-relaxed text-gray-400">
            Thông tin được truy xuất từ hệ thống VISAFO theo Thông tư 02/2024/TT-BKHCN.<br>
            Mã TXNG chỉ có giá trị cho đúng lần đóng gói in trên tem.
        </p>
    </main>
    @endunless
</div>
</body>
</html>
