{{-- Tab "Thương hiệu" trên trang truy xuất. Biến: $trace, $icon (từ show.blade.php). --}}

{{-- 1. Câu chuyện thương hiệu (config trace.brand_story) --}}
<section class="overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-black/5">
    <div class="flex items-center gap-3 bg-gradient-to-br from-green-700 to-green-600 px-4 py-4 text-white">
        <img src="{{ asset('images/visafo-mark.png') }}" alt="{{ $trace->brand }}" class="h-14 w-14 shrink-0 rounded-full bg-white object-contain p-1.5">
        <div class="min-w-0">
            <p class="text-lg font-bold leading-tight">{{ $trace->brand }}</p>
            <p class="text-xs leading-snug text-green-50">{{ $trace->company['name'] }}</p>
            @if(config('trace.company_slogan'))
            <p class="mt-1 text-sm italic text-white/90">“{{ config('trace.company_slogan') }}”</p>
            @endif
        </div>
    </div>
    <div class="p-4">
        <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
            <span class="h-4 w-1 rounded bg-green-600"></span> Câu chuyện thương hiệu
        </h3>
        @forelse(array_filter(array_map('trim', preg_split('/\R/', $trace->brandStory))) as $paragraph)
        <p class="text-[15px] leading-relaxed text-gray-700 {{ $loop->first ? '' : 'mt-2' }}">{{ $paragraph }}</p>
        @empty
        <p class="text-sm text-gray-500">Đang cập nhật.</p>
        @endforelse
        @if($trace->company['address'] || $trace->company['hotline'] !== '')
        <ul class="mt-3 divide-y divide-gray-100 border-t border-gray-100">
            @if($trace->company['address'])
            @include('traceability.partials.row', ['ic' => 'building', 'label' => 'Trụ sở', 'value' => $trace->company['address']])
            @endif
            @if($trace->company['hotline'] !== '')
            <li class="flex items-center gap-3 py-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon['phone'] }}"/></svg>
                </span>
                <a href="tel:{{ preg_replace('/\s+/', '', $trace->company['hotline']) }}" class="font-semibold text-green-700">Hotline: {{ $trace->company['hotline'] }}</a>
            </li>
            @endif
        </ul>
        @endif
    </div>
</section>

{{-- 2. Hồ sơ doanh nghiệp: nhóm "Hồ sơ pháp lý cơ sở", đang hiệu lực, loại giấy tờ bật "Công khai trên trang truy xuất" --}}
<section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
    <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
        <span class="h-4 w-1 rounded bg-green-600"></span> Hồ sơ doanh nghiệp
    </h3>
    <p class="mb-3 text-xs text-gray-500">Giấy tờ pháp lý &amp; chứng nhận đang hiệu lực. Chạm vào ảnh để xem bản scan.</p>

    @if($trace->companyDocuments)
    <div class="grid grid-cols-2 gap-3">
        @foreach($trace->companyDocuments as $doc)
        @php
            $first = $doc['files'][0] ?? null;
            // Lightbox: mọi tệp của hồ sơ — ảnh lớn (PDF: trang 1 đã render) + link mở PDF đầy đủ
            $slides = array_map(fn ($f) => ['img' => $f['isPdf'] ? $f['preview'] : $f['url'], 'pdf' => $f['isPdf'] ? $f['url'] : null], $doc['files']);
        @endphp
        <article class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white">
            @if($first === null)
            <div class="flex aspect-[3/4] flex-col items-center justify-center gap-1.5 bg-gray-50 text-gray-300">
                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon['shield'] }}"/></svg>
                <span class="text-[11px] text-gray-400">Chưa có bản scan</span>
            </div>
            @else
            <button type="button" data-trace-lightbox='@json($slides)' aria-label="Xem phóng to {{ $doc['name'] }}"
                    class="relative block aspect-[3/4] overflow-hidden bg-gray-100">
                {{-- Icon dự phòng nằm dưới ảnh: ảnh thu nhỏ lỗi (PDF hỏng/mã hóa) thì tự gỡ ảnh, lộ icon --}}
                @if($first['isPdf'])
                <span class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-red-50 text-red-600">
                    <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                    <span class="rounded bg-red-600 px-1.5 py-0.5 text-[11px] font-bold tracking-wider text-white">PDF</span>
                </span>
                @else
                <span class="absolute inset-0 flex items-center justify-center text-gray-300">
                    <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon['shield'] }}"/></svg>
                </span>
                @endif
                <img src="{{ $first['thumb'] }}" alt="{{ $doc['name'] }}" class="relative h-full w-full object-cover object-top" loading="lazy" onerror="this.remove()">
                @if($first['isPdf'])
                <span class="absolute left-1.5 top-1.5 rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-white">PDF</span>
                @endif
                @if(count($doc['files']) > 1)
                <span class="absolute right-1.5 top-1.5 rounded-full bg-black/55 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ count($doc['files']) }} tệp</span>
                @endif
                <span class="absolute bottom-1.5 right-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-black/55 text-white backdrop-blur">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 8v6m-3-3h6m5 0a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                </span>
            </button>
            @endif
            <div class="flex flex-1 flex-col p-2.5">
                <p class="line-clamp-3 text-[13px] font-semibold leading-snug text-gray-900">{{ $doc['name'] }}</p>
                @if($doc['number'])<p class="mt-0.5 truncate text-[11px] text-gray-500">Số: <span class="font-mono">{{ $doc['number'] }}</span></p>@endif
                @if($doc['issuedBy'])<p class="line-clamp-2 text-[11px] text-gray-500">Cấp bởi: {{ $doc['issuedBy'] }}</p>@endif
                <p class="mt-auto flex flex-wrap items-center gap-x-1.5 gap-y-0.5 pt-1.5">
                    <span class="inline-block rounded-full bg-green-600 px-2 py-0.5 text-[10px] font-semibold text-white">Đang hiệu lực</span>
                    @if($doc['expiresAt'])<span class="text-[10px] text-gray-500">đến {{ $doc['expiresAt']->format('d/m/Y') }}</span>@endif
                </p>
            </div>
        </article>
        @endforeach
    </div>
    @else
    <p class="text-sm text-gray-500">Đang cập nhật hồ sơ doanh nghiệp.</p>
    @endif
</section>

{{-- Lightbox xem bản scan (các tệp của một hồ sơ xếp dọc, cuộn được) --}}
<div data-trace-lightbox-root hidden class="fixed inset-0 z-50 flex flex-col bg-black/90" role="dialog" aria-modal="true" aria-label="Xem hồ sơ">
    <div class="flex justify-end p-3">
        <button type="button" data-trace-lightbox-close aria-label="Đóng"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div data-trace-lightbox-body class="flex-1 space-y-3 overflow-y-auto px-3 pb-6"></div>
</div>
