{{-- Tab "VISAFO" trên trang truy xuất. Biến: $trace, $icon, $pillClass (từ show.blade.php). --}}

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

{{-- 2. Hồ sơ pháp lý & năng lực: mỗi hồ sơ là một nút pill, bấm mở lightbox (tệp phát qua route trace.document).
     Nhóm theo thứ tự các tab ở internal-compliance; nhóm không có hồ sơ công khai đã bị bỏ ở backend. --}}
<section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-green-700">
        <span class="h-4 w-1 rounded bg-green-600"></span> Hồ sơ pháp lý &amp; năng lực
    </h3>
    @forelse($trace->documentGroups as $group)
    <div class="{{ $loop->first ? '' : 'mt-4' }}">
        @if(count($trace->documentGroups) > 1)
        <p class="mb-2 text-xs font-medium text-gray-500">{{ $group['label'] }}</p>
        @endif
        <div class="flex flex-wrap gap-2">
            @foreach($group['documents'] as $doc)
            @php
                $hasPdf = collect($doc['files'])->contains('isPdf', true);
                // Lightbox: mọi tệp của hồ sơ — ảnh lớn (PDF: trang 1 đã render) + link mở PDF đầy đủ
                $slides = array_map(fn ($f) => ['img' => $f['isPdf'] ? $f['preview'] : $f['url'], 'pdf' => $f['isPdf'] ? $f['url'] : null], $doc['files']);
                $caption = implode(' · ', array_filter([
                    $doc['name'],
                    $doc['number'] ? 'Số ' . $doc['number'] : null,
                    $doc['issuedBy'] ? 'Cấp bởi ' . $doc['issuedBy'] : null,
                    $doc['expiresAt'] ? 'Hiệu lực đến ' . $doc['expiresAt']->format('d/m/Y') : 'Đang hiệu lực',
                ]));
            @endphp
            @if($doc['files'])
            <button type="button" data-trace-lightbox='@json($slides)' data-trace-caption="{{ $caption }}" aria-label="Xem {{ $doc['name'] }}" title="{{ $caption }}"
                    class="{{ $pillClass }}">
                @if($hasPdf)
                <svg class="h-4 w-4 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                @else
                <svg class="h-4 w-4 shrink-0 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon['shield'] }}"/></svg>
                @endif
                <span class="truncate">Xem {{ $doc['name'] }}</span>
                @if(count($doc['files']) > 1)
                <span class="shrink-0 rounded-full bg-white px-1.5 text-[10px] font-semibold text-gray-500">{{ count($doc['files']) }}</span>
                @endif
            </button>
            @else
            {{-- Hồ sơ đang hiệu lực nhưng chưa có bản scan công khai: hiện tên, không bấm được --}}
            <span title="{{ $caption }}" class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-dashed border-gray-200 bg-gray-50 py-1.5 pl-2.5 pr-3 text-[13px] text-gray-400">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon['shield'] }}"/></svg>
                <span class="truncate">{{ $doc['name'] }}</span>
            </span>
            @endif
            @endforeach
        </div>
    </div>
    @empty
    <p class="text-sm text-gray-500">Đang cập nhật hồ sơ doanh nghiệp.</p>
    @endforelse
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
