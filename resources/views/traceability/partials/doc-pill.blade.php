{{--
    Nút pill mở hồ sơ trên trang truy xuất. Biến: $label, $iconHtml (emoji/svg đã escape sẵn), $files (array{url, preview, isPdf}[]),
    tùy chọn $caption, $pillClass (từ show.blade.php).
    - Không có tệp  → không render gì.
    - Đúng 1 tệp    → <a target="_blank">: trình duyệt tự mở ảnh/PDF ở tab mới, trang truy xuất không phải giữ tài liệu trong bộ nhớ.
    - Nhiều tệp     → nút lightbox: JS tải trước ảnh (spinner, khóa nút), tải xong mới mở; lỗi → toast + trả nút.
--}}
@php $caption = $caption ?? $label; @endphp
@if(! $files)
{{-- không có tệp → không render --}}
@elseif(count($files) === 1)
<a href="{{ $files[0]['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $caption }}" class="{{ $pillClass }}">
    <span aria-hidden="true">{!! $iconHtml !!}</span>
    <span class="truncate">{{ $label }}</span>
    <span aria-hidden="true" class="text-gray-400">›</span>
</a>
@else
<button type="button" title="{{ $caption }}" class="{{ $pillClass }} disabled:pointer-events-none disabled:opacity-70"
        data-trace-lightbox='@json(array_map(fn ($f) => ['img' => $f['isPdf'] ? $f['preview'] : $f['url'], 'pdf' => $f['isPdf'] ? $f['url'] : null], $files))'
        data-trace-caption="{{ $caption }}">
    <span aria-hidden="true" data-pill-icon>{!! $iconHtml !!}</span>
    <span class="truncate">{{ $label }}</span>
    <span class="shrink-0 rounded-full bg-white px-1.5 text-[10px] font-semibold text-gray-500">{{ count($files) }}</span>
</button>
@endif
